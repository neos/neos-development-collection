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
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphIdentity;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Core\Projection\ContentGraph\CoverageByOrigin;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\Projection\ContentGraph\OriginByCoverage;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
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
        private readonly ContentRepositoryId $contentRepositoryId,
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
        return new Node(
            ContentSubgraphIdentity::create(
                $this->contentRepositoryId,
                ContentStreamId::fromString($nodeRow['contentstreamid']),
                $dimensionSpacePoint,
                $visibilityConstraints
            ),
            NodeAggregateId::fromString($nodeRow['nodeaggregateid']),
            OriginDimensionSpacePoint::fromJsonString($nodeRow['origindimensionspacepoint']),
            NodeAggregateClassification::from($nodeRow['classification']),
            NodeTypeName::fromString($nodeRow['nodetypename']),
            $this->nodeTypeManager->getNodeType($nodeRow['nodetypename']),
            $this->createPropertyCollectionFromJsonString($nodeRow['properties']),
            isset($nodeRow['name']) ? NodeName::fromString($nodeRow['name']) : null,
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $nodeRow['createdat']),
            \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $nodeRow['originalcreatedat']),
            isset($nodeRow['lastmodifiedat']) ? \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $nodeRow['lastmodifiedat']) : null,
            isset($nodeRow['originallastmodifiedat']) ? \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $nodeRow['originallastmodifiedat']) : null,
        );
    }

    /**
     * @param array<int, array<string, mixed>> $nodeRows
     */
    public function mapNodeRowsToNodes(array $nodeRows, DimensionSpacePoint $dimensionSpacePoint, VisibilityConstraints $visibilityConstraints): Nodes
    {
        return Nodes::fromArray(
            array_map(fn (array $nodeRow) => $this->mapNodeRowToNode($nodeRow, $dimensionSpacePoint, $visibilityConstraints), $nodeRows)
        );
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

        $rawNodeAggregateId = '';
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
                $rawNodeAggregateId = $rawNodeAggregateId ?: $nodeRow['nodeaggregateid'];
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
        ksort($occupiedDimensionSpacePoints);
        ksort($coveredDimensionSpacePoints);
        ksort($disabledDimensionSpacePoints);

        /** @var Node $primaryNode  a nodeAggregate only exists if it at least contains one node. */
        $primaryNode = current($nodesByOccupiedDimensionSpacePoints);

        return new NodeAggregate(
            $primaryNode->subgraphIdentity->contentStreamId,
            NodeAggregateId::fromString($rawNodeAggregateId),
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
     * @return iterable<int,NodeAggregate>
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
            $rawNodeAggregateId = $nodeRow['nodeaggregateid'];
            $occupiedDimensionSpacePoint = OriginDimensionSpacePoint::fromJsonString(
                $nodeRow['origindimensionspacepoint']
            );
            if (
                !isset($nodesByOccupiedDimensionSpacePointsByNodeAggregate
                [$rawNodeAggregateId][$occupiedDimensionSpacePoint->hash])
            ) {
                // ... so we handle occupation exactly once ...
                $nodesByOccupiedDimensionSpacePointsByNodeAggregate
                    [$rawNodeAggregateId][$occupiedDimensionSpacePoint->hash] = $this->mapNodeRowToNode(
                        $nodeRow,
                        $occupiedDimensionSpacePoint->toDimensionSpacePoint(),
                        $visibilityConstraints
                    );
                $occupiedDimensionSpacePointsByNodeAggregate[$rawNodeAggregateId][]
                    = $occupiedDimensionSpacePoint;
                $nodeTypeNames[$rawNodeAggregateId] = $nodeTypeNames[$rawNodeAggregateId]
                    ?? NodeTypeName::fromString($nodeRow['nodetypename']);
                $nodeNames[$rawNodeAggregateId] = $nodeNames[$rawNodeAggregateId]
                    ?? ($nodeRow['name'] ? NodeName::fromString($nodeRow['name']) : null);
                $classificationByNodeAggregate[$rawNodeAggregateId]
                    = $classificationByNodeAggregate[$rawNodeAggregateId]
                    ?? NodeAggregateClassification::from($nodeRow['classification']);
            }
            // ... and coverage always ...
            $coveredDimensionSpacePoint = DimensionSpacePoint::fromJsonString(
                $nodeRow['covereddimensionspacepoint']
            );
            $coverageByOccupantsByNodeAggregate[$rawNodeAggregateId][$occupiedDimensionSpacePoint->hash]
                [$coveredDimensionSpacePoint->hash] = $coveredDimensionSpacePoint;
            $occupationByCoveringByNodeAggregate[$rawNodeAggregateId][$coveredDimensionSpacePoint->hash]
                = $occupiedDimensionSpacePoint;

            $coveredDimensionSpacePointsByNodeAggregate[$rawNodeAggregateId][$coveredDimensionSpacePoint->hash]
                = $coveredDimensionSpacePoint;
            $nodesByCoveredDimensionSpacePointsByNodeAggregate
                [$rawNodeAggregateId][$coveredDimensionSpacePoint->hash]
                = $nodesByOccupiedDimensionSpacePointsByNodeAggregate
                    [$rawNodeAggregateId][$occupiedDimensionSpacePoint->hash];

            // ... as we do for disabling
            if (isset($nodeRow['disableddimensionspacepointhash'])) {
                $disabledDimensionSpacePointsByNodeAggregate
                    [$rawNodeAggregateId][$coveredDimensionSpacePoint->hash] = $coveredDimensionSpacePoint;
            }
        }

        foreach ($nodesByOccupiedDimensionSpacePointsByNodeAggregate as $rawNodeAggregateId => $nodes) {
            /** @var string $rawNodeAggregateId */
            yield new NodeAggregate(
                // this line is safe because a nodeAggregate only exists if it at least contains one node.
                current($nodes)->subgraphIdentity->contentStreamId,
                NodeAggregateId::fromString($rawNodeAggregateId),
                $classificationByNodeAggregate[$rawNodeAggregateId],
                $nodeTypeNames[$rawNodeAggregateId],
                $nodeNames[$rawNodeAggregateId],
                new OriginDimensionSpacePointSet(
                    $occupiedDimensionSpacePointsByNodeAggregate[$rawNodeAggregateId]
                ),
                $nodes,
                CoverageByOrigin::fromArray(
                    $coverageByOccupantsByNodeAggregate[$rawNodeAggregateId]
                ),
                new DimensionSpacePointSet(
                    $coveredDimensionSpacePointsByNodeAggregate[$rawNodeAggregateId]
                ),
                $nodesByCoveredDimensionSpacePointsByNodeAggregate
                    [$rawNodeAggregateId],
                OriginByCoverage::fromArray(
                    $occupationByCoveringByNodeAggregate[$rawNodeAggregateId]
                ),
                new DimensionSpacePointSet(
                    $disabledDimensionSpacePointsByNodeAggregate[$rawNodeAggregateId] ?? []
                )
            );
        }
    }
}
