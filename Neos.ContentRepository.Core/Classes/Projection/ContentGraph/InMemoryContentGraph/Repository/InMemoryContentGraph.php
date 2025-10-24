<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Repository;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindRootNodeAggregatesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection\InMemoryContentGraphStructure;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection\InMemoryNodeRecord;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregates;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * The In-Memory adapter content graph
 *
 * To be used as a read-only source of nodes
 *
 * @internal the parent interface {@see ContentGraphInterface} is API
 */
final class InMemoryContentGraph implements ContentGraphInterface
{
    public function __construct(
        private readonly InMemoryContentGraphStructure $graphStructure,
        private readonly NodeFactory $nodeFactory,
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly NodeTypeManager $nodeTypeManager,
        public readonly WorkspaceName $workspaceName,
        public readonly ContentStreamId $contentStreamId
    ) {
    }

    public function getContentRepositoryId(): ContentRepositoryId
    {
        return $this->contentRepositoryId;
    }

    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    public function getSubgraph(
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): ContentSubgraphInterface {
        return new InMemoryContentSubgraph(
            $this->contentRepositoryId,
            $this->workspaceName,
            $this->contentStreamId,
            $dimensionSpacePoint,
            $visibilityConstraints,
            $this->nodeFactory,
            $this->nodeTypeManager,
            $this->graphStructure,
        );
    }

    public function findRootNodeAggregateByType(
        NodeTypeName $nodeTypeName
    ): ?NodeAggregate {
        $rootNodeAggregates = $this->findRootNodeAggregates(
            FindRootNodeAggregatesFilter::create(nodeTypeName: $nodeTypeName)
        );

        if ($rootNodeAggregates->count() > 1) {
            // todo drop this check as this is enforced by the write side? https://github.com/neos/neos-development-collection/pull/4339
            $ids = [];
            foreach ($rootNodeAggregates as $rootNodeAggregate) {
                $ids[] = $rootNodeAggregate->nodeAggregateId->value;
            }

            // We throw if multiple root node aggregates of the given $nodeTypeName were found,
            // as this would lead to nondeterministic results. Must not happen.
            throw new \RuntimeException(sprintf(
                'More than one root node aggregate of type "%s" found (IDs: %s).',
                $nodeTypeName->value,
                implode(', ', $ids)
            ));
        }

        return $rootNodeAggregates->first();
    }

    public function findRootNodeAggregates(
        FindRootNodeAggregatesFilter $filter,
    ): NodeAggregates {
        if ($filter->nodeTypeName) {
            $rootNodes = $this->graphStructure->rootNodes[$this->contentStreamId->value][$filter->nodeTypeName->value] ?? null;
            if ($rootNodes === null) {
                return NodeAggregates::createEmpty();
            }
            $rootNodeRecords = [$filter->nodeTypeName->value => [$rootNodes]];
        } else {
            $rootNodeRecords = $this->graphStructure->rootNodes;
        }
        return NodeAggregates::fromArray(
            array_map(
                fn (array $nodeRecords): NodeAggregate => $this->nodeFactory->mapNodeRecordsToNodeAggregate($nodeRecords, $this->workspaceName, $this->contentStreamId),
                $rootNodeRecords
            )
        );
    }

    public function findNodeAggregatesByType(
        NodeTypeName $nodeTypeName
    ): NodeAggregates {
        throw new \Exception(__METHOD__ . ' not implemented yet');
    }

    public function findNodeAggregateById(
        NodeAggregateId $nodeAggregateId
    ): ?NodeAggregate {
        $nodeRecords = $this->graphStructure->nodes[$this->contentStreamId->value][$nodeAggregateId->value] ?? null;

        return $nodeRecords === null
            ? null
            : $this->nodeFactory->mapNodeRecordsToNodeAggregate($nodeRecords, $this->workspaceName, $this->contentStreamId);
    }

    public function findNodeAggregatesByIds(
        NodeAggregateIds $nodeAggregateIds
    ): NodeAggregates {
        $nodeAggregates = [];
        foreach ($nodeAggregateIds as $nodeAggregateId) {
            $nodeAggregate = $this->findNodeAggregateById($nodeAggregateId);
            if ($nodeAggregate !== null) {
                $nodeAggregates[] = $nodeAggregate;
            }
        }

        return NodeAggregates::fromArray($nodeAggregates);
    }

    /**
     * Parent node aggregates can have a greater dimension space coverage than the given child.
     * Thus, it is not enough to just resolve them from the nodes and edges connected to the given child node aggregate.
     * Instead, we resolve all parent node aggregate ids instead and fetch the complete aggregates from there.
     */
    public function findParentNodeAggregates(
        NodeAggregateId $childNodeAggregateId
    ): NodeAggregates {
        $parentNodeAggregateIds = NodeAggregateIds::createEmpty();
        foreach ($this->graphStructure->nodes[$this->contentStreamId->value][$childNodeAggregateId->value] as $nodeRecord) {
            $parentNodeAggregateIds = $parentNodeAggregateIds->merge(
                $nodeRecord->parentsByContentStreamId[$this->contentStreamId->value]->getParentNodeAggregateIds()
            );
        }

        return $this->findNodeAggregatesByIds(NodeAggregateIds::create(...$parentNodeAggregateIds));
    }

    public function findAncestorNodeAggregateIds(NodeAggregateId $entryNodeAggregateId): NodeAggregateIds
    {
        $nodeAggregateIds = NodeAggregateIds::create();
        foreach ($this->graphStructure->nodes[$this->contentStreamId->value][$entryNodeAggregateId->value] as $nodeRecord) {
            foreach ($nodeRecord->parentsByContentStreamId[$this->contentStreamId->value] as $parentHierarchyRelation) {
                if ($parentHierarchyRelation->parent === null) {
                    continue;
                }
                $nodeAggregateIds = $nodeAggregateIds->merge(NodeAggregateIds::create($parentHierarchyRelation->parent->nodeAggregateId));
                $nodeAggregateIds = $nodeAggregateIds->merge($this->findAncestorNodeAggregateIds($parentHierarchyRelation->parent->nodeAggregateId));
            }
        }

        return $nodeAggregateIds;
    }

    public function findChildNodeAggregates(
        NodeAggregateId $parentNodeAggregateId
    ): NodeAggregates {
        $childNodeAggregateIds = [];
        foreach ($this->graphStructure->nodes[$this->contentStreamId->value][$parentNodeAggregateId->value] ?? [] as $nodeRecord) {
            foreach ($nodeRecord->childrenByContentStream[$this->contentStreamId->value] as $childRelation) {
                foreach ($childRelation->children as $childNodeRecord) {
                    $childNodeAggregateIds[$childNodeRecord->nodeAggregateId->value] = $childNodeRecord->nodeAggregateId;
                }
            }
        }

        return $this->findNodeAggregatesByIds(NodeAggregateIds::create(...$childNodeAggregateIds));
    }

    public function findParentNodeAggregateByChildOriginDimensionSpacePoint(NodeAggregateId $childNodeAggregateId, OriginDimensionSpacePoint $childOriginDimensionSpacePoint): ?NodeAggregate
    {
        $childNodeRecord = $this->graphStructure->nodes[$this->contentStreamId->value][$childNodeAggregateId->value][$childOriginDimensionSpacePoint->hash] ?? null;

        $parentNode = $childNodeRecord?->parentsByContentStreamId[$this->contentStreamId->value]
            ->getHierarchyHyperrelation($childOriginDimensionSpacePoint->toDimensionSpacePoint())
            ->parent;

        if ($parentNode instanceof InMemoryNodeRecord) {
            return $this->findNodeAggregateById($parentNode->nodeAggregateId);
        }

        return null;
    }

    public function findTetheredChildNodeAggregates(NodeAggregateId $parentNodeAggregateId): NodeAggregates
    {
        $nodeRecords = [];
        foreach ($this->graphStructure->nodes[$this->contentStreamId->value][$parentNodeAggregateId->value] ?? [] as $nodeRecord) {
            foreach ($nodeRecord->childrenByContentStream[$this->contentStreamId->value] as $childRelation) {
                foreach ($childRelation->children as $childNodeRecord) {
                    if ($childNodeRecord->classification === NodeAggregateClassification::CLASSIFICATION_TETHERED) {
                        $nodeRecords[] = $childNodeRecord;
                    }
                }
            }
        }
        if ($nodeRecords === []) {
            return NodeAggregates::createEmpty();
        }

        return $this->nodeFactory->mapNodeRecordsToNodeAggregates($nodeRecords, $this->workspaceName, $this->contentStreamId);
    }

    public function findChildNodeAggregateByName(
        NodeAggregateId $parentNodeAggregateId,
        NodeName $name
    ): ?NodeAggregate {
        $nodeRecords = [];
        foreach ($this->graphStructure->nodes[$this->contentStreamId->value][$parentNodeAggregateId->value] ?? [] as $nodeRecord) {
            foreach ($nodeRecord->childrenByContentStream[$this->contentStreamId->value] as $childRelation) {
                foreach ($childRelation->children as $childNodeRecord) {
                    if ($childNodeRecord?->name->equals($name)) {
                        $nodeRecords[] = $childNodeRecord;
                        break;
                    }
                }
            }
        }
        if ($nodeRecords === []) {
            return null;
        }

        return $this->nodeFactory->mapNodeRecordsToNodeAggregate($nodeRecords, $this->workspaceName, $this->contentStreamId);
    }

    public function getDimensionSpacePointsOccupiedByChildNodeName(NodeName $nodeName, NodeAggregateId $parentNodeAggregateId, OriginDimensionSpacePoint $parentNodeOriginDimensionSpacePoint, DimensionSpacePointSet $dimensionSpacePointsToCheck): DimensionSpacePointSet
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
    }

    public function findNodeAggregatesTaggedBy(SubtreeTag $subtreeTag): NodeAggregates
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
    }

    public function findUsedNodeTypeNames(): NodeTypeNames
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
    }

    public function getContentStreamId(): ContentStreamId
    {
        return $this->contentStreamId;
    }
}
