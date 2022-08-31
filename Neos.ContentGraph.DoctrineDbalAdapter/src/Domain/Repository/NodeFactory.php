<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Factory\ContentRepositoryIdentifier;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphIdentity;
use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Core\Projection\ContentGraph\CoverageByOrigin;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\Projection\ContentGraph\OriginByCoverage;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;

/**
 * Implementation detail of ContentGraph and ContentSubgraph
 *
 * @internal
 */
final class NodeFactory
{
    public function __construct(
        private readonly ContentRepositoryIdentifier $contentRepositoryIdentifier,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly PropertyConverter $propertyConverter
    ) {
    }

    /**
     * @param array<string,string> $nodeRow Node Row from projection (<prefix>_node table)
     * @throws NodeTypeNotFoundException
     */
    public function mapNodeRowToNode(
        array $nodeRow,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): Node {
        $nodeType = $this->nodeTypeManager->getNodeType($nodeRow['nodetypename']);
        $node = new Node(
            ContentSubgraphIdentity::create(
                $this->contentRepositoryIdentifier,
                ContentStreamIdentifier::fromString($nodeRow['contentstreamidentifier']),
                $dimensionSpacePoint,
                $visibilityConstraints
            ),
            NodeAggregateIdentifier::fromString($nodeRow['nodeaggregateidentifier']),
            OriginDimensionSpacePoint::fromJsonString($nodeRow['origindimensionspacepoint']),
            NodeAggregateClassification::from($nodeRow['classification']),
            NodeTypeName::fromString($nodeRow['nodetypename']),
            $nodeType,
            $this->createPropertyCollectionFromJsonString($nodeRow['properties']),
            isset($nodeRow['name']) ? NodeName::fromString($nodeRow['name']) : null,
        );

        return $node;
    }

    public function createPropertyCollectionFromJsonString(string $jsonString): PropertyCollection
    {
        return new PropertyCollection(
            SerializedPropertyValues::fromJsonString($jsonString),
            $this->propertyConverter
        );
    }

    /**
     * @param array<int,array<string,mixed>> $nodeRows
     */
    public function mapReferenceRowsToReferences(
        array $nodeRows,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): References {
        $result = [];
        foreach ($nodeRows as $nodeRow) {
            $node = $this->mapNodeRowToNode(
                $nodeRow,
                $dimensionSpacePoint,
                $visibilityConstraints
            );
            $result[] = new Reference(
                $node,
                PropertyName::fromString($nodeRow['referencename']),
                $nodeRow['referenceproperties']
                    ? $this->createPropertyCollectionFromJsonString($nodeRow['referenceproperties'])
                    : null
            );
        }

        return References::fromArray($result);
    }

    /**
     * @param array<int,array<string,string>> $nodeRows
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

            $coverageByOccupants[$occupiedDimensionSpacePoint->hash][$coveredDimensionSpacePoint->hash]
                = $coveredDimensionSpacePoint;
            $occupationByCovering[$coveredDimensionSpacePoint->hash] = $occupiedDimensionSpacePoint;
            $nodesByCoveredDimensionSpacePoints[$coveredDimensionSpacePoint->hash]
                = $nodesByOccupiedDimensionSpacePoints[$occupiedDimensionSpacePoint->hash];
            // ... as we do for disabling
            if (isset($nodeRow['disableddimensionspacepointhash'])) {
                $disabledDimensionSpacePoints[$coveredDimensionSpacePoint->hash] = $coveredDimensionSpacePoint;
            }
        }

        /** @var Node $primaryNode  a nodeAggregate only exists if it at least contains one node. */
        $primaryNode = current($nodesByOccupiedDimensionSpacePoints);

        return new \Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate(
            $primaryNode->subgraphIdentity->contentStreamIdentifier,
            NodeAggregateIdentifier::fromString($rawNodeAggregateIdentifier),
            NodeAggregateClassification::from($rawNodeAggregateClassification),
            NodeTypeName::fromString($rawNodeTypeName),
            $rawNodeName ? NodeName::fromString($rawNodeName) : null,
            new OriginDimensionSpacePointSet($occupiedDimensionSpacePoints),
            $nodesByOccupiedDimensionSpacePoints,
            CoverageByOrigin::fromArray($coverageByOccupants),
            new DimensionSpacePointSet($coveredDimensionSpacePoints),
            $nodesByCoveredDimensionSpacePoints,
            OriginByCoverage::fromArray($occupationByCovering),
            new DimensionSpacePointSet($disabledDimensionSpacePoints)
        );
    }

    /**
     * @param iterable<int,array<string,string>> $nodeRows
     * @return iterable<int,\Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate>
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
            if (
                !isset($nodesByOccupiedDimensionSpacePointsByNodeAggregate
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
            $coverageByOccupantsByNodeAggregate[$rawNodeAggregateIdentifier][$occupiedDimensionSpacePoint->hash]
                [$coveredDimensionSpacePoint->hash] = $coveredDimensionSpacePoint;
            $occupationByCoveringByNodeAggregate[$rawNodeAggregateIdentifier][$coveredDimensionSpacePoint->hash]
                = $occupiedDimensionSpacePoint;

            $coveredDimensionSpacePointsByNodeAggregate[$rawNodeAggregateIdentifier][$coveredDimensionSpacePoint->hash]
                = $coveredDimensionSpacePoint;
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

        foreach ($nodesByOccupiedDimensionSpacePointsByNodeAggregate as $rawNodeAggregateIdentifier => $nodes) {
            /** @var string $rawNodeAggregateIdentifier */
            yield new \Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate(
                // this line is safe because a nodeAggregate only exists if it at least contains one node.
                current($nodes)->subgraphIdentity->contentStreamIdentifier,
                NodeAggregateIdentifier::fromString($rawNodeAggregateIdentifier),
                $classificationByNodeAggregate[$rawNodeAggregateIdentifier],
                $nodeTypeNames[$rawNodeAggregateIdentifier],
                $nodeNames[$rawNodeAggregateIdentifier],
                new OriginDimensionSpacePointSet(
                    $occupiedDimensionSpacePointsByNodeAggregate[$rawNodeAggregateIdentifier]
                ),
                $nodes,
                CoverageByOrigin::fromArray(
                    $coverageByOccupantsByNodeAggregate[$rawNodeAggregateIdentifier]
                ),
                new DimensionSpacePointSet(
                    $coveredDimensionSpacePointsByNodeAggregate[$rawNodeAggregateIdentifier]
                ),
                $nodesByCoveredDimensionSpacePointsByNodeAggregate
                    [$rawNodeAggregateIdentifier],
                OriginByCoverage::fromArray(
                    $occupationByCoveringByNodeAggregate[$rawNodeAggregateIdentifier]
                ),
                new DimensionSpacePointSet(
                    $disabledDimensionSpacePointsByNodeAggregate[$rawNodeAggregateIdentifier] ?? []
                )
            );
        }
    }
}
