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
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\Nodes;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;

/**
 * The node factory for mapping database rows to nodes and node aggregates
 */
final class NodeFactory
{
    private NodeTypeManager $nodeTypeManager;

    public function __construct(NodeTypeManager $nodeTypeManager)
    {
        $this->nodeTypeManager = $nodeTypeManager;
    }

    public function mapNodeRowsToNodeAggregate(array $nodeRows): ?NodeAggregate
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
            $node = $this->mapNodeRowToNode($nodeRow);
            $contentStreamIdentifier = $contentStreamIdentifier ?: ContentStreamIdentifier::fromString($nodeRow['contentstreamidentifier']);
            $nodeAggregateIdentifier = $nodeAggregateIdentifier ?: NodeAggregateIdentifier::fromString($nodeRow['nodeaggregateidentifier']);
            $nodeAggregateClassification = $nodeAggregateClassification ?: NodeAggregateClassification::fromString($nodeRow['classification']);
            $nodeTypeName = $nodeTypeName ?: NodeTypeName::fromString($nodeRow['nodetypename']);
            if (!empty($nodeRow['nodename']) && is_null($nodeName)) {
                $nodeName = NodeName::fromString($nodeRow['nodename']);
            }
            $occupiedDimensionSpacePoints[$node->getOriginDimensionSpacePoint()->getHash()] = $node->getOriginDimensionSpacePoint();
            $nodesByOccupiedDimensionSpacePoint[$node->getOriginDimensionSpacePoint()->getHash()] = $node;

            $coveredDimensionSpacePoint = DimensionSpacePoint::fromJsonString($nodeRow['dimensionspacepoint']);
            $coverageByOccupant[$node->getOriginDimensionSpacePoint()->getHash()][$coveredDimensionSpacePoint->getHash()] = $coveredDimensionSpacePoint;
            $coveredDimensionSpacePoints[$coveredDimensionSpacePoint->getHash()] = $coveredDimensionSpacePoint;
            $nodesByCoveredDimensionSpacePoint[$coveredDimensionSpacePoint->getHash()] = $node;
            $occupationByCovered[$coveredDimensionSpacePoint->getHash()] = $node->getOriginDimensionSpacePoint();
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
    public function mapNodeRowsToNodeAggregates(array $nodeRows): array
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
            $node = $this->mapNodeRowToNode($nodeRow);
            $contentStreamIdentifier = $contentStreamIdentifier ?: ContentStreamIdentifier::fromString($nodeRow['contentstreamidentifier']);
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
            $occupiedDimensionSpacePoints[$key][$node->getOriginDimensionSpacePoint()->getHash()] = $node->getOriginDimensionSpacePoint();
            $nodesByOccupiedDimensionSpacePoint[$key][$node->getOriginDimensionSpacePoint()->getHash()] = $node;

            $coveredDimensionSpacePoint = DimensionSpacePoint::fromJsonString($nodeRow['dimensionspacepoint']);
            $coveredDimensionSpacePoints[$key][$coveredDimensionSpacePoint->getHash()] = $coveredDimensionSpacePoint;
            $coverageByOccupant[$key][$node->getOriginDimensionSpacePoint()->getHash()][$coveredDimensionSpacePoint->getHash()] = $coveredDimensionSpacePoint;
            $nodesByCoveredDimensionSpacePoint[$key][$coveredDimensionSpacePoint->getHash()] = $node;
            $occupationByCovered[$key][$coveredDimensionSpacePoint->getHash()] = $node->getOriginDimensionSpacePoint();
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

    public function mapNodeRowToNode(array $nodeRow, ContentStreamIdentifier $contentStreamIdentifier = null): NodeInterface
    {
        return new Node(
            $contentStreamIdentifier ?: ContentStreamIdentifier::fromString($nodeRow['contentstreamidentifier']),
            NodeAggregateIdentifier::fromString($nodeRow['nodeaggregateidentifier']),
            OriginDimensionSpacePoint::fromJsonString($nodeRow['origindimensionspacepoint']),
            NodeTypeName::fromString($nodeRow['nodetypename']),
            $this->nodeTypeManager->getNodeType($nodeRow['nodetypename']),
            !empty($nodeRow['nodename']) ? NodeName::fromString($nodeRow['nodename']) : null,
            SerializedPropertyValues::fromJsonString($nodeRow['properties']),
            NodeAggregateClassification::fromString($nodeRow['classification'])
        );
    }

    public function mapNodeRowsToNodes(array $nodeRows, ContentStreamIdentifier $contentStreamIdentifier = null): Nodes
    {
        $nodes = [];
        foreach ($nodeRows as $nodeRow) {
            $nodes[] = $this->mapNodeRowToNode($nodeRow, $contentStreamIdentifier);
        }

        return Nodes::fromArray($nodes);
    }

    public function mapNodeRowsToSubtree(array $nodeRows): Subtree
    {
        $subtreesByParentNodeAggregateIdentifier = [];
        foreach ($nodeRows as $nodeRow) {
            $node = $this->mapNodeRowToNode($nodeRow);
            $subtreesByParentNodeAggregateIdentifier[$nodeRow['parentnodeaggregateidentifier']][] = new Subtree(
                $nodeRow['level'],
                $node,
                $subtreesByParentNodeAggregateIdentifier[$nodeRow['nodeaggregateidentifier']] ?? []
            );
        }

        return $subtreesByParentNodeAggregateIdentifier['ROOT'][0];
    }
}
