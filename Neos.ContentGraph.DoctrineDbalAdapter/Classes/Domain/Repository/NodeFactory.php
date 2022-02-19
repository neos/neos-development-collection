<?php
declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository;

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Node;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\ContentRepository\Domain\Service\NodeTypeManager;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Exception\NodeTypeNotFoundException;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateClassification;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePointSet;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Projection\Content as ContentProjection;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\Exception\NodeImplementationClassNameIsInvalid;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeAggregate;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\PropertyCollection;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\EventSourcedContentRepository\Infrastructure\Property\PropertyConverter;
use Neos\Flow\Annotations as Flow;

/**
 * Implementation detail of ContentGraph and ContentSubgraph
 *
 * @Flow\Scope("singleton")
 */
final class NodeFactory
{
    private NodeTypeManager $nodeTypeManager;
    private PropertyConverter $propertyConverter;

    public function __construct(NodeTypeManager $nodeTypeManager, PropertyConverter $propertyConverter)
    {
        $this->nodeTypeManager = $nodeTypeManager;
        $this->propertyConverter = $propertyConverter;
    }

    /**
     * @param array $nodeRow Node Row from projection (neos_contentgraph_node table)
     * @return Node
     * @throws NodeTypeNotFoundException
     */
    public function mapNodeRowToNode(
        array $nodeRow,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): NodeInterface {
        $nodeType = $this->nodeTypeManager->getNodeType($nodeRow['nodetypename']);
        $nodeClassName = $nodeType->getConfiguration('class') ?: Node::class;
        if (!class_exists($nodeClassName)) {
            throw NodeImplementationClassNameIsInvalid::becauseTheClassDoesNotExist($nodeClassName);
        }
        if (!in_array(NodeInterface::class, class_implements($nodeClassName))) {
            if (in_array(NodeInterface::class, class_implements($nodeClassName))) {
                throw NodeImplementationClassNameIsInvalid::becauseTheClassImplementsTheDeprecatedLegacyInterface(
                    $nodeClassName
                );
            }
            throw NodeImplementationClassNameIsInvalid::becauseTheClassDoesNotImplementTheRequiredInterface(
                $nodeClassName
            );
        }
        return new $nodeClassName(
            ContentStreamIdentifier::fromString($nodeRow['contentstreamidentifier']),
            NodeAggregateIdentifier::fromString($nodeRow['nodeaggregateidentifier']),
            OriginDimensionSpacePoint::fromJsonString($nodeRow['origindimensionspacepoint']),
            NodeTypeName::fromString($nodeRow['nodetypename']),
            $nodeType,
            isset($nodeRow['name']) ? NodeName::fromString($nodeRow['name']) : null,
            new PropertyCollection(
                SerializedPropertyValues::fromJsonString($nodeRow['properties']),
                $this->propertyConverter
            ),
            NodeAggregateClassification::from($nodeRow['classification']),
            $dimensionSpacePoint,
            $visibilityConstraints
        );
    }

    /**
     * @param array $nodeRows
     * @return NodeAggregate|null
     * @throws NodeTypeNotFoundException
     */
    public function mapNodeRowsToNodeAggregate(
        array $nodeRows,
        VisibilityConstraints $visibilityConstraints
    ): ?NodeAggregate {
        if (empty($nodeRows)) {
            return null;
        }

        $rawNodeAggregateIdentifier = '';
        $rawNodeTypeName = '';
        $rawNodeName = '';
        $rawNodeAggregateClassification = '';
        $occupiedDimensionSpacePoints = [];
        $nodesByOccupiedDimensionSpacePoints = [];
        $coveredDimensionSpacePoints = [];
        $nodesByCoveredDimensionSpacePoints = [];
        $coverageByOccupants = [];
        $occupationByCovering = [];
        $disabledDimensionSpacePoints = [];

        foreach ($nodeRows as $nodeRow) {
            // A node can occupy exactly one DSP and cover multiple ones...
            $occupiedDimensionSpacePoint = OriginDimensionSpacePoint::fromJsonString(
                $nodeRow['origindimensionspacepoint']
            );
            if (!isset($nodesByOccupiedDimensionSpacePoints[$occupiedDimensionSpacePoint->hash])) {
                // ... so we handle occupation exactly once ...
                $nodesByOccupiedDimensionSpacePoints[$occupiedDimensionSpacePoint->hash] = $this->mapNodeRowToNode(
                    $nodeRow,
                    $occupiedDimensionSpacePoint->toDimensionSpacePoint(),
                    $visibilityConstraints
                );
                $occupiedDimensionSpacePoints[] = $occupiedDimensionSpacePoint;
                $rawNodeAggregateIdentifier = $rawNodeAggregateIdentifier ?: $nodeRow['nodeaggregateidentifier'];
                $rawNodeTypeName = $rawNodeTypeName ?: $nodeRow['nodetypename'];
                $rawNodeName = $rawNodeName ?: $nodeRow['name'];
                $rawNodeAggregateClassification = $rawNodeAggregateClassification ?: $nodeRow['classification'];
            }
            // ... and coverage always ...
            $coveredDimensionSpacePoint = DimensionSpacePoint::fromJsonString(
                $nodeRow['covereddimensionspacepoint']
            );
            $coveredDimensionSpacePoints[$coveredDimensionSpacePoint->hash] = $coveredDimensionSpacePoint;

            $coverageByOccupants[$occupiedDimensionSpacePoint->hash][] = $coveredDimensionSpacePoint;
            $occupationByCovering[$coveredDimensionSpacePoint->hash] = $occupiedDimensionSpacePoint;
            $nodesByCoveredDimensionSpacePoints[$coveredDimensionSpacePoint->hash]
                = $nodesByOccupiedDimensionSpacePoints[$occupiedDimensionSpacePoint->hash];
            // ... as we do for disabling
            if (isset($nodeRow['disableddimensionspacepointhash'])) {
                $disabledDimensionSpacePoints[$coveredDimensionSpacePoint->hash] = $coveredDimensionSpacePoint;
            }
        }

        foreach ($coverageByOccupants as &$coverage) {
            $coverage = new DimensionSpacePointSet($coverage);
        }

        return new ContentProjection\NodeAggregate(
            // this line is safe because a nodeAggregate only exists if it at least contains one node.
            current($nodesByOccupiedDimensionSpacePoints)->getContentStreamIdentifier(),
            NodeAggregateIdentifier::fromString($rawNodeAggregateIdentifier),
            NodeAggregateClassification::from($rawNodeAggregateClassification),
            NodeTypeName::fromString($rawNodeTypeName),
            $rawNodeName ? NodeName::fromString($rawNodeName) : null,
            new OriginDimensionSpacePointSet($occupiedDimensionSpacePoints),
            $nodesByOccupiedDimensionSpacePoints,
            $coverageByOccupants,
            new DimensionSpacePointSet($coveredDimensionSpacePoints),
            $nodesByCoveredDimensionSpacePoints,
            $occupationByCovering,
            new DimensionSpacePointSet($disabledDimensionSpacePoints)
        );
    }

    /**
     * @param iterable $nodeRows
     * @return iterable<ContentProjection\NodeAggregate>
     * @throws NodeTypeNotFoundException
     */
    public function mapNodeRowsToNodeAggregates(
        iterable $nodeRows,
        VisibilityConstraints $visibilityConstraints
    ): iterable {
        $nodeTypeNames = [];
        $nodeNames = [];
        $occupiedDimensionSpacePointsByNodeAggregate = [];
        $nodesByOccupiedDimensionSpacePointsByNodeAggregate = [];
        $coveredDimensionSpacePointsByNodeAggregate = [];
        $nodesByCoveredDimensionSpacePointsByNodeAggregate = [];
        $classificationByNodeAggregate = [];
        $coverageByOccupantsByNodeAggregate = [];
        $occupationByCoveringByNodeAggregate = [];
        $disabledDimensionSpacePointsByNodeAggregate = [];

        foreach ($nodeRows as $nodeRow) {
            // A node can occupy exactly one DSP and cover multiple ones...
            $rawNodeAggregateIdentifier = $nodeRow['nodeaggregateidentifier'];
            $occupiedDimensionSpacePoint = OriginDimensionSpacePoint::fromJsonString(
                $nodeRow['origindimensionspacepoint']
            );
            if (!isset($nodesByOccupiedDimensionSpacePointsByNodeAggregate
                [$rawNodeAggregateIdentifier][$occupiedDimensionSpacePoint->hash])
            ) {
                // ... so we handle occupation exactly once ...
                $nodesByOccupiedDimensionSpacePointsByNodeAggregate
                    [$rawNodeAggregateIdentifier][$occupiedDimensionSpacePoint->hash] = $this->mapNodeRowToNode(
                        $nodeRow,
                        $occupiedDimensionSpacePoint->toDimensionSpacePoint(),
                        $visibilityConstraints
                    );
                $occupiedDimensionSpacePointsByNodeAggregate[$rawNodeAggregateIdentifier][]
                    = $occupiedDimensionSpacePoint;
                $nodeTypeNames[$rawNodeAggregateIdentifier] = $nodeTypeNames[$rawNodeAggregateIdentifier]
                    ?? NodeTypeName::fromString($nodeRow['nodetypename']);
                $nodeNames[$rawNodeAggregateIdentifier] = $nodeNames[$rawNodeAggregateIdentifier]
                    ?? ($nodeRow['name'] ? NodeName::fromString($nodeRow['name']) : null);
                $classificationByNodeAggregate[$rawNodeAggregateIdentifier]
                    = $classificationByNodeAggregate[$rawNodeAggregateIdentifier]
                    ?? NodeAggregateClassification::from($nodeRow['classification']);
            }
            // ... and coverage always ...
            $coveredDimensionSpacePoint = DimensionSpacePoint::fromJsonString(
                $nodeRow['covereddimensionspacepoint']
            );
            $coverageByOccupantsByNodeAggregate[$rawNodeAggregateIdentifier][$occupiedDimensionSpacePoint->hash][]
                = $coveredDimensionSpacePoint;
            $occupationByCoveringByNodeAggregate[$rawNodeAggregateIdentifier][$coveredDimensionSpacePoint->hash]
                = $occupiedDimensionSpacePoint;

            $coveredDimensionSpacePointsByNodeAggregate[$rawNodeAggregateIdentifier][] = $coveredDimensionSpacePoint;
            $nodesByCoveredDimensionSpacePointsByNodeAggregate
                [$rawNodeAggregateIdentifier][$coveredDimensionSpacePoint->hash]
                = $nodesByOccupiedDimensionSpacePointsByNodeAggregate
                    [$rawNodeAggregateIdentifier][$occupiedDimensionSpacePoint->hash];

            // ... as we do for disabling
            if (isset($nodeRow['disableddimensionspacepointhash'])) {
                $disabledDimensionSpacePointsByNodeAggregate
                    [$rawNodeAggregateIdentifier][$coveredDimensionSpacePoint->hash] = $coveredDimensionSpacePoint;
            }
        }

        foreach ($coverageByOccupantsByNodeAggregate as &$coverageByOccupants) {
            foreach ($coverageByOccupants as &$coverage) {
                $coverage = new DimensionSpacePointSet($coverage);
            }
        }

        foreach ($nodesByOccupiedDimensionSpacePointsByNodeAggregate as $rawNodeAggregateIdentifier => $nodes) {
            yield new ContentProjection\NodeAggregate(
                // this line is safe because a nodeAggregate only exists if it at least contains one node.
                current($nodes)->getContentStreamIdentifier(),
                NodeAggregateIdentifier::fromString($rawNodeAggregateIdentifier),
                $classificationByNodeAggregate[$rawNodeAggregateIdentifier],
                $nodeTypeNames[$rawNodeAggregateIdentifier],
                $nodeNames[$rawNodeAggregateIdentifier],
                new OriginDimensionSpacePointSet(
                    $occupiedDimensionSpacePointsByNodeAggregate[$rawNodeAggregateIdentifier]
                ),
                $nodes,
                $coverageByOccupantsByNodeAggregate[$rawNodeAggregateIdentifier],
                new DimensionSpacePointSet(
                    $coveredDimensionSpacePointsByNodeAggregate[$rawNodeAggregateIdentifier]
                ),
                $nodesByCoveredDimensionSpacePointsByNodeAggregate
                    [$rawNodeAggregateIdentifier],
                $occupationByCoveringByNodeAggregate[$rawNodeAggregateIdentifier],
                new DimensionSpacePointSet(
                    $disabledDimensionSpacePointsByNodeAggregate[$rawNodeAggregateIdentifier] ?? []
                )
            );
        }
    }
}
