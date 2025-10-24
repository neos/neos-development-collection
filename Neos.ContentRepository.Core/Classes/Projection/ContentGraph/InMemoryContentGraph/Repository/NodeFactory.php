<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Repository;

use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePointSet;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;
use Neos\ContentRepository\Core\Projection\ContentGraph\CoverageByOrigin;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection\InMemoryNodeRecord;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection\InMemoryReferenceRecord;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregates;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\Projection\ContentGraph\OriginByCoverage;
use Neos\ContentRepository\Core\Projection\ContentGraph\PropertyCollection;
use Neos\ContentRepository\Core\Projection\ContentGraph\Reference;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtrees;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * Implementation detail of ContentGraph and ContentSubgraph
 *
 * @internal
 */
final class NodeFactory
{
    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly PropertyConverter $propertyConverter,
    ) {
    }

    public function mapNodeRecordToNode(
        InMemoryNodeRecord $nodeRecord,
        WorkspaceName $workspaceName,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints,
    ): Node {
        return Node::create(
            $this->contentRepositoryId,
            $workspaceName,
            $dimensionSpacePoint,
            $nodeRecord->nodeAggregateId,
            $nodeRecord->originDimensionSpacePoint,
            $nodeRecord->classification,
            $nodeRecord->nodeTypeName,
            new PropertyCollection(
                $nodeRecord->properties,
                $this->propertyConverter
            ),
            $nodeRecord->name,
            NodeTags::create(
                $nodeRecord->tags,
                $nodeRecord->inheritedTags,
            ),
            $nodeRecord->timestamps,
            $visibilityConstraints
        );
    }

    /**
     * @param iterable<int, InMemoryNodeRecord> $nodeRecords
     */
    public function mapNodeRecordsToNodes(
        iterable $nodeRecords,
        WorkspaceName $workspaceName,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): Nodes {
        return Nodes::fromArray(
            array_map(
                fn (InMemoryNodeRecord $nodeRecord) => $this->mapNodeRecordToNode(
                    $nodeRecord,
                    $workspaceName,
                    $dimensionSpacePoint,
                    $visibilityConstraints
                ),
                iterator_to_array($nodeRecords)
            )
        );
    }

    /**
     * @param array<int,InMemoryReferenceRecord> $referenceRecords
     */
    public function mapReferenceRecordsToReferences(
        array $referenceRecords,
        WorkspaceName $workspaceName,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints,
        bool $backwards,
    ): References {
        $result = [];
        foreach ($referenceRecords as $referenceRecord) {
            $node = $this->mapNodeRecordToNode(
                $backwards ? $referenceRecord->source : $referenceRecord->target,
                $workspaceName,
                $dimensionSpacePoint,
                $visibilityConstraints
            );
            $result[] = new Reference(
                $node,
                $referenceRecord->name,
                $referenceRecord->properties
                    ? new PropertyCollection(
                        $referenceRecord->properties,
                        $this->propertyConverter
                    )
                    : null,
            );
        }

        return References::fromArray($result);
    }

    /**
     * @param non-empty-array<int, InMemoryNodeRecord> $nodeRecords
     */
    public function mapNodeRecordsToNodeAggregate(
        array $nodeRecords,
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId
    ): NodeAggregate {
        $nodeAggregateId = null;
        $classification = null;
        $nodeTypeName = null;
        $nodeName = null;
        $occupiedDimensionSpacePoints = [];
        $nodesByOccupiedDimensionSpacePoint = [];
        $coverageByOccupant = [];
        $totalCoveredDimensionSpacePoints = DimensionSpacePointSet::fromArray([]);
        $occupationByCovered = [];
        $nodeTagsByCoveredDimensionSpacePoint = [];
        foreach ($nodeRecords as $nodeRecord) {
            $nodeAggregateId = $nodeRecord->nodeAggregateId;
            $classification = $nodeRecord->classification;
            $nodeTypeName = $nodeRecord->nodeTypeName;
            $nodeName = $nodeRecord->name;
            $occupiedDimensionSpacePoints[] = $nodeRecord->originDimensionSpacePoint;
            $nodesByOccupiedDimensionSpacePoint[$nodeRecord->originDimensionSpacePoint->hash] = $this->mapNodeRecordToNode(
                $nodeRecord,
                $workspaceName,
                $nodeRecord->originDimensionSpacePoint->toDimensionSpacePoint(),
                VisibilityConstraints::createEmpty()
            );
            $coveredDimensionSpacePoints = $nodeRecord->parentsByContentStreamId[$contentStreamId->value]->getCoveredDimensionSpacePointSet();
            $coverageByOccupant[$nodeRecord->originDimensionSpacePoint->hash] = iterator_to_array($coveredDimensionSpacePoints);
            $totalCoveredDimensionSpacePoints = $totalCoveredDimensionSpacePoints->getUnion($coveredDimensionSpacePoints);
            foreach ($coveredDimensionSpacePoints as $coveredDimensionSpacePoint) {
                $occupationByCovered[$coveredDimensionSpacePoint->hash] = $nodeRecord->originDimensionSpacePoint;
                $nodeTagsByCoveredDimensionSpacePoint[$coveredDimensionSpacePoint->hash] = NodeTags::create(
                    $nodeRecord->tags,
                    $nodeRecord->inheritedTags
                );
            }
        }

        return NodeAggregate::create(
            $this->contentRepositoryId,
            $workspaceName,
            $nodeAggregateId,
            $classification,
            $nodeTypeName,
            $nodeName,
            new OriginDimensionSpacePointSet($occupiedDimensionSpacePoints),
            $nodesByOccupiedDimensionSpacePoint,
            CoverageByOrigin::fromArray($coverageByOccupant),
            $totalCoveredDimensionSpacePoints,
            OriginByCoverage::fromArray($occupationByCovered),
            /** @phpstan-ignore argument.type (never empty) */
            $nodeTagsByCoveredDimensionSpacePoint,
        );
    }

    /**
     * @param array<int, InMemoryNodeRecord> $nodeRecords
     */
    public function mapNodeRecordsToNodeAggregates(
        array $nodeRecords,
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId
    ): NodeAggregates {
        $nodeRecordsByAggregateId = [];
        foreach ($nodeRecords as $nodeRecord) {
            $nodeRecordsByAggregateId[$nodeRecord->nodeAggregateId->value][] = $nodeRecord;
        }

        return NodeAggregates::fromArray(array_map(
            fn (array $nodeRecords): NodeAggregate
                => $this->mapNodeRecordsToNodeAggregate($nodeRecords, $workspaceName, $contentStreamId),
            $nodeRecordsByAggregateId
        ));
    }

    public function mapNodeRecordToSubtree(
        InMemoryNodeRecord $nodeRecord,
        WorkspaceName $workspaceName,
        ContentStreamId $contentStreamId,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints,
        int $level,
        FindSubtreeFilter $filter,
    ): Subtree {
        $continue = $filter->maximumLevels === null || $level <= $filter->maximumLevels;
        /** @todo apply node type filters */
        return Subtree::create(
            $level,
            $this->mapNodeRecordToNode($nodeRecord, $workspaceName, $dimensionSpacePoint, $visibilityConstraints),
            $continue
                ? Subtrees::fromArray(array_map(
                    fn (InMemoryNodeRecord $nodeRecord): Subtree => $this->mapNodeRecordToSubtree(
                        $nodeRecord,
                        $workspaceName,
                        $contentStreamId,
                        $dimensionSpacePoint,
                        $visibilityConstraints,
                        $level + 1,
                        $filter,
                    ),
                    iterator_to_array(
                        $nodeRecord->childrenByContentStream[$contentStreamId->value]
                            ->getHierarchyHyperrelation($dimensionSpacePoint)
                            ?->children ?: []
                    )
                ))
                : Subtrees::createEmpty()
        );
    }
}
