<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Repository;

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Content\Node;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateClassification;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePointSet;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\Nodes;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\PropertyCollection;
use Neos\EventSourcedContentRepository\Domain\Projection\Content as ContentProjection;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\EventSourcedContentRepository\Infrastructure\Property\PropertyConverter;

/**
 * The node factory for mapping database rows to nodes and node aggregates
 */
final class NodeFactory
{
    private NodeTypeManager $nodeTypeManager;

    private PropertyConverter $propertyConverter;

    public function __construct(
        NodeTypeManager $nodeTypeManager,
        PropertyConverter $propertyConverter
    ) {
        $this->nodeTypeManager = $nodeTypeManager;
        $this->propertyConverter = $propertyConverter;
    }

    public function mapNodeRowToNode(
        array $nodeRow,
        VisibilityConstraints $visibilityConstraints,
        ?DimensionSpacePoint $dimensionSpacePoint = null,
        ?ContentStreamIdentifier $contentStreamIdentifier = null
    ): NodeInterface {
        $nodeType = $this->nodeTypeManager->getNodeType($nodeRow['nodetypename']);
        $nodeClassName = $nodeType->getConfiguration('class') ?: \Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Node::class;
        if (!class_exists($nodeClassName)) {
            throw ContentProjection\Exception\NodeImplementationClassNameIsInvalid::becauseTheClassDoesNotExist($nodeClassName);
        }
        if (!in_array(ContentProjection\NodeInterface::class, class_implements($nodeClassName))) {
            if (in_array(\Neos\ContentRepository\Domain\Projection\Content\NodeInterface::class, class_implements($nodeClassName))) {
                throw ContentProjection\Exception\NodeImplementationClassNameIsInvalid::becauseTheClassImplementsTheDeprecatedLegacyInterface($nodeClassName);
            }
            throw ContentProjection\Exception\NodeImplementationClassNameIsInvalid::becauseTheClassDoesNotImplementTheRequiredInterface($nodeClassName);
        }
        return new $nodeClassName(
            $contentStreamIdentifier ?: ContentStreamIdentifier::fromString($nodeRow['contentstreamidentifier']),
            NodeAggregateIdentifier::fromString($nodeRow['nodeaggregateidentifier']),
            OriginDimensionSpacePoint::fromJsonString($nodeRow['origindimensionspacepoint']),
            NodeTypeName::fromString($nodeRow['nodetypename']),
            $nodeType,
            $nodeRow['nodename'] ? NodeName::fromString($nodeRow['nodename']) : null,
            new PropertyCollection(SerializedPropertyValues::fromArray(json_decode($nodeRow['properties'], true)), $this->propertyConverter),
            NodeAggregateClassification::fromString($nodeRow['classification']),
            $dimensionSpacePoint ?: DimensionSpacePoint::fromJsonString($nodeRow['dimensionspacepoint']),
            $visibilityConstraints
        );
    }

    public function mapNodeRowsToNodes(
        array $nodeRows,
        VisibilityConstraints $visibilityConstraints,
        ContentStreamIdentifier $contentStreamIdentifier = null
    ): Nodes {
        $nodes = [];
        foreach ($nodeRows as $nodeRow) {
            $nodes[] = $this->mapNodeRowToNode(
                $nodeRow,
                $visibilityConstraints,
                null,
                $contentStreamIdentifier
            );
        }

        return Nodes::fromArray($nodes);
    }

    public function mapNodeRowsToSubtree(
        array $nodeRows,
        VisibilityConstraints $visibilityConstraints
    ): Subtree {
        $subtreesByParentNodeAggregateIdentifier = [];
        foreach ($nodeRows as $nodeRow) {
            $node = $this->mapNodeRowToNode(
                $nodeRow,
                $visibilityConstraints
            );
            $subtreesByParentNodeAggregateIdentifier[$nodeRow['parentnodeaggregateidentifier']][] = new Subtree(
                $nodeRow['level'],
                $node,
                $subtreesByParentNodeAggregateIdentifier[$nodeRow['nodeaggregateidentifier']] ?? []
            );
        }

        return $subtreesByParentNodeAggregateIdentifier['ROOT'][0];
    }

    public function mapNodeRowsToNodeAggregate(array $nodeRows, VisibilityConstraints $visibilityConstraints): ?NodeAggregate
    {
        if (empty($nodeRows)) {
            return null;
        }

        $contentStreamIdentifier = null;
        $nodeAggregateIdentifier = null;
        $nodeAggregateClassification = null;
        $nodeTypeName = null;
        $nodeName = null;
        /** @var OriginDimensionSpacePoint[] $occupiedDimensionSpacePoints */
        $occupiedDimensionSpacePoints = [];
        $nodesByOccupiedDimensionSpacePoint = [];
        /** @var DimensionSpacePoint[][] $coverageByOccupant */
        $coverageByOccupant = [];
        /** @var DimensionSpacePoint[] $coveredDimensionSpacePoints */
        $coveredDimensionSpacePoints = [];
        $nodesByCoveredDimensionSpacePoint = [];
        $occupationByCovered = [];
        /** @var DimensionSpacePoint[] $disabledDimensionSpacePoints */
        $disabledDimensionSpacePoints = [];
        foreach ($nodeRows as $nodeRow) {
            $contentStreamIdentifier = $contentStreamIdentifier ?: ContentStreamIdentifier::fromString($nodeRow['contentstreamidentifier']);
            $node = $this->mapNodeRowToNode(
                $nodeRow,
                $visibilityConstraints,
                null,
                $contentStreamIdentifier
            );
            $nodeAggregateIdentifier = $nodeAggregateIdentifier ?: NodeAggregateIdentifier::fromString($nodeRow['nodeaggregateidentifier']);
            $nodeAggregateClassification = $nodeAggregateClassification ?: NodeAggregateClassification::fromString($nodeRow['classification']);
            $nodeTypeName = $nodeTypeName ?: NodeTypeName::fromString($nodeRow['nodetypename']);
            if (!empty($nodeRow['nodename']) && is_null($nodeName)) {
                $nodeName = NodeName::fromString($nodeRow['nodename']);
            }
            $occupiedDimensionSpacePoints[$node->getOriginDimensionSpacePoint()->hash] = $node->getOriginDimensionSpacePoint();
            $nodesByOccupiedDimensionSpacePoint[$node->getOriginDimensionSpacePoint()->hash] = $node;

            $coveredDimensionSpacePoint = DimensionSpacePoint::fromJsonString($nodeRow['dimensionspacepoint']);
            $coverageByOccupant[$node->getOriginDimensionSpacePoint()->hash][$coveredDimensionSpacePoint->hash] = $coveredDimensionSpacePoint;
            $coveredDimensionSpacePoints[$coveredDimensionSpacePoint->hash] = $coveredDimensionSpacePoint;
            $nodesByCoveredDimensionSpacePoint[$coveredDimensionSpacePoint->hash] = $node;
            $occupationByCovered[$coveredDimensionSpacePoint->hash] = $node->getOriginDimensionSpacePoint();
            if (isset($nodeRow['disableddimensionspacepointhash']) && $nodeRow['disableddimensionspacepointhash']) {
                $disabledDimensionSpacePoints[$nodeRow['disableddimensionspacepointhash']] = $coveredDimensionSpacePoints[$nodeRow['disableddimensionspacepointhash']];
            }
        }

        return new NodeAggregate(
            $contentStreamIdentifier,
            $nodeAggregateIdentifier,
            $nodeAggregateClassification,
            $nodeTypeName,
            $nodeName,
            new OriginDimensionSpacePointSet($occupiedDimensionSpacePoints),
            $nodesByOccupiedDimensionSpacePoint,
            array_map(function (array $dimensionSpacePoints) {
                return DimensionSpacePointSet::fromArray($dimensionSpacePoints);
            }, $coverageByOccupant),
            new DimensionSpacePointSet($coveredDimensionSpacePoints),
            $nodesByCoveredDimensionSpacePoint,
            $occupationByCovered,
            new DimensionSpacePointSet($disabledDimensionSpacePoints)
        );
    }

    /**
     * @param array $nodeRows
     * @return array|NodeAggregate[]
     */
    public function mapNodeRowsToNodeAggregates(array $nodeRows, VisibilityConstraints $visibilityConstraints): array
    {
        $nodeAggregates = [];
        if (empty($nodeRows)) {
            return $nodeAggregates;
        }

        $contentStreamIdentifier = null;
        /** @var NodeAggregateIdentifier[] $nodeAggregateIdentifiers */
        $nodeAggregateIdentifiers = [];
        /** @var NodeAggregateClassification[] $nodeAggregateClassifications */
        $nodeAggregateClassifications = [];
        /** @var NodeTypeName[] $nodeTypeNames */
        $nodeTypeNames = [];
        /** @var NodeName[] $nodeNames */
        $nodeNames = [];
        /** @var OriginDimensionSpacePoint[][] $occupiedDimensionSpacePoints */
        $occupiedDimensionSpacePoints = [];
        /** @var Node[][] $nodesByOccupiedDimensionSpacePoint */
        $nodesByOccupiedDimensionSpacePoint = [];
        /** @var DimensionSpacePoint[][][] $coverageByOccupant */
        $coverageByOccupant = [];
        /** @var DimensionSpacePoint[][] $coveredDimensionSpacePoints */
        $coveredDimensionSpacePoints = [];
        /** @var Node[][] $nodesByCoveredDimensionSpacePoint */
        $nodesByCoveredDimensionSpacePoint = [];
        /** @var OriginDimensionSpacePoint[][] $occupationByCovered */
        $occupationByCovered = [];
        /** @var DimensionSpacePoint[][] $disabledDimensionSpacePoints */
        $disabledDimensionSpacePoints = [];
        foreach ($nodeRows as $nodeRow) {
            $key = $nodeRow['nodeaggregateidentifier'];
            $contentStreamIdentifier = $contentStreamIdentifier ?: ContentStreamIdentifier::fromString($nodeRow['contentstreamidentifier']);
            $node = $this->mapNodeRowToNode(
                $nodeRow,
                $visibilityConstraints,
                null,
                $contentStreamIdentifier
            );
            $nodeAggregateIdentifiers[$key] = NodeAggregateIdentifier::fromString($nodeRow['nodeaggregateidentifier']);
            if (!isset($nodeAggregateClassifications[$key])) {
                $nodeAggregateClassifications[$key] = NodeAggregateClassification::fromString($nodeRow['classification']);
            }
            if (!isset($nodeTypeNames[$key])) {
                $nodeTypeNames[$key] = NodeTypeName::fromString($nodeRow['nodetypename']);
            }
            if (!empty($nodeRow['nodename']) && !isset($nodeNames[$nodeRow['nodename']])) {
                $nodeNames[$key] = NodeName::fromString($nodeRow['nodename']);
            } else {
                $nodeNames[$key] = null;
            }
            $occupiedDimensionSpacePoints[$key][$node->getOriginDimensionSpacePoint()->hash] = $node->getOriginDimensionSpacePoint();
            $nodesByOccupiedDimensionSpacePoint[$key][$node->getOriginDimensionSpacePoint()->hash] = $node;

            $coveredDimensionSpacePoint = DimensionSpacePoint::fromJsonString($nodeRow['dimensionspacepoint']);
            $coveredDimensionSpacePoints[$key][$coveredDimensionSpacePoint->hash] = $coveredDimensionSpacePoint;
            $coverageByOccupant[$key][$node->getOriginDimensionSpacePoint()->hash][$coveredDimensionSpacePoint->hash] = $coveredDimensionSpacePoint;
            $nodesByCoveredDimensionSpacePoint[$key][$coveredDimensionSpacePoint->hash] = $node;
            $occupationByCovered[$key][$coveredDimensionSpacePoint->hash] = $node->getOriginDimensionSpacePoint();
            if (!isset($disabledDimensionSpacePoints[$key])) {
                $disabledDimensionSpacePoints[$key] = [];
            }
            if (isset($nodeRow['disableddimensionspacepointhash']) && $nodeRow['disableddimensionspacepointhash']) {
                $disabledDimensionSpacePoints[$key][$nodeRow['disableddimensionspacepointhash']] = $coveredDimensionSpacePoints[$key][$nodeRow['disableddimensionspacepointhash']];
            }
        }

        foreach ($nodeAggregateIdentifiers as $key => $nodeAggregateIdentifier) {
            $nodeAggregates[$key] = new NodeAggregate(
                $contentStreamIdentifier,
                $nodeAggregateIdentifier,
                $nodeAggregateClassifications[$key],
                $nodeTypeNames[$key],
                $nodeNames[$key],
                new OriginDimensionSpacePointSet($occupiedDimensionSpacePoints[$key]),
                $nodesByOccupiedDimensionSpacePoint[$key],
                $coverageByOccupant[$key],
                new DimensionSpacePointSet($coveredDimensionSpacePoints[$key]),
                $nodesByCoveredDimensionSpacePoint[$key],
                $occupationByCovered[$key],
                new DimensionSpacePointSet($disabledDimensionSpacePoints[$key])
            );
        }

        return $nodeAggregates;
    }
}
