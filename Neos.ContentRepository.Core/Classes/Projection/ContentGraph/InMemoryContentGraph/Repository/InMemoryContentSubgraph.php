<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Repository;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\AbsoluteNodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountAncestorNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\CountReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindAncestorNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindChildNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindDescendantNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection\InMemoryContentGraphStructure;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection\InMemoryNodeRecord;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection\InMemoryNodeRecords;
use Neos\ContentRepository\Core\Projection\ContentGraph\InMemoryContentGraph\Projection\InMemoryReferenceRecord;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\References;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * The in-memory content subgraph application repository
 *
 * To be used as a read-only source of nodes.
 *
 * @internal the parent {@see ContentSubgraphInterface} is API
 */
final class InMemoryContentSubgraph implements ContentSubgraphInterface
{
    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly WorkspaceName $workspaceName,
        private readonly ContentStreamId $contentStreamId,
        private readonly DimensionSpacePoint $dimensionSpacePoint,
        private readonly VisibilityConstraints $visibilityConstraints,
        private readonly NodeFactory $nodeFactory,
        private readonly NodeTypeManager $nodeTypeManager,
        private readonly InMemoryContentGraphStructure $graphStructure,
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

    public function getDimensionSpacePoint(): DimensionSpacePoint
    {
        return $this->dimensionSpacePoint;
    }

    public function getVisibilityConstraints(): VisibilityConstraints
    {
        return $this->visibilityConstraints;
    }

    public function findChildNodes(NodeAggregateId $parentNodeAggregateId, FindChildNodesFilter $filter): Nodes
    {
        /** @todo apply filters */
        foreach ($this->graphStructure->nodes[$this->contentStreamId->value][$parentNodeAggregateId->value] as $parentNodeRecord) {
            $relation = $parentNodeRecord->childrenByContentStream[$this->contentStreamId->value]->getHierarchyHyperrelation($this->dimensionSpacePoint);
            if ($relation !== null) {
                return $this->mapNodeRecordsToNodes($relation->children);
            }
        }

        return Nodes::createEmpty();
    }

    public function countChildNodes(NodeAggregateId $parentNodeAggregateId, CountChildNodesFilter $filter): int
    {
        return count($this->findChildNodes($parentNodeAggregateId, FindChildNodesFilter::create(
            $filter->nodeTypes,
            $filter->searchTerm,
            $filter->propertyValue,
        )));
    }

    public function findReferences(NodeAggregateId $nodeAggregateId, FindReferencesFilter $filter): References
    {
        /** @todo apply filters */
        $references = [];
        $referenceRelations = $this->graphStructure->references[$this->contentStreamId->value] ?? null;
        if ($referenceRelations === null) {
            return References::fromArray([]);
        }
        foreach ($referenceRelations as $referenceRecords) {
            if ($referenceRelations->getInfo() === $this->dimensionSpacePoint) {
                foreach ($referenceRecords as $referenceRecord) {
                    if ($referenceRecord->source->nodeAggregateId === $nodeAggregateId) {
                        $references[] = $this->mapReferenceRecordsToReferences($referenceRecord, false);
                    }
                }
                break;
            }
        }

        return References::fromArray($references);
    }

    public function countReferences(NodeAggregateId $nodeAggregateId, CountReferencesFilter $filter): int
    {
        return count($this->findReferences($nodeAggregateId, FindReferencesFilter::create(
            $filter->nodeTypes,
            $filter->nodeSearchTerm,
            $filter->nodePropertyValue,
            $filter->referenceSearchTerm,
            $filter->referencePropertyValue,
            $filter->referenceName,
        )));
    }

    public function findBackReferences(NodeAggregateId $nodeAggregateId, FindBackReferencesFilter $filter): References
    {
        /** @todo apply filters */
        $references = [];
        $referenceRelations = $this->graphStructure->references[$this->contentStreamId->value] ?? null;
        if ($referenceRelations === null) {
            return References::fromArray([]);
        }
        foreach ($referenceRelations as $referenceRecords) {
            if ($referenceRelations->getInfo() === $this->dimensionSpacePoint) {
                foreach ($referenceRecords as $referenceRecord) {
                    if ($referenceRecord->target->nodeAggregateId === $nodeAggregateId) {
                        $references[] = $this->mapReferenceRecordsToReferences($referenceRecord, true);
                    }
                }
                break;
            }
        }

        return References::fromArray($references);
    }

    public function countBackReferences(NodeAggregateId $nodeAggregateId, CountBackReferencesFilter $filter): int
    {
        return count($this->findBackReferences($nodeAggregateId, FindBackReferencesFilter::create(
            $filter->nodeTypes,
            $filter->nodeSearchTerm,
            $filter->nodePropertyValue,
            $filter->referenceSearchTerm,
            $filter->referencePropertyValue,
            $filter->referenceName,
        )));
    }

    public function findNodeById(NodeAggregateId $nodeAggregateId): ?Node
    {
        $nodeRecord = $this->findNodeRecord($nodeAggregateId);

        return $nodeRecord !== null
            ? $this->mapNodeRecordToNode($nodeRecord)
            : null;
    }

    public function findNodesByIds(NodeAggregateIds $nodeAggregateIds): Nodes
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
    }

    public function findRootNodeByType(NodeTypeName $nodeTypeName): ?Node
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
    }

    public function findParentNode(NodeAggregateId $childNodeAggregateId): ?Node
    {
        $parentNodeRecord = $this->findParentNodeRecord($childNodeAggregateId);

        return $parentNodeRecord
            ? $this->mapNodeRecordToNode($parentNodeRecord)
            : null;
    }

    public function findNodeByPath(NodePath|NodeName $path, NodeAggregateId $startingNodeAggregateId): ?Node
    {
        $path = $path instanceof NodeName ? NodePath::fromNodeNames($path) : $path;

        $startingNode = $this->findNodeById($startingNodeAggregateId);

        return $startingNode
            ? $this->findNodeByPathFromStartingNode($path, $startingNode)
            : null;
    }

    public function findNodeByAbsolutePath(AbsoluteNodePath $path): ?Node
    {
        $startingNode = $this->findRootNodeByType($path->rootNodeTypeName);

        return $startingNode
            ? $this->findNodeByPathFromStartingNode($path->path, $startingNode)
            : null;
    }

    /**
     * Find a single child node by its name
     *
     * @return Node|null the node that is connected to its parent with the specified $nodeName, or NULL if no matching node exists or the parent node is not accessible
     */
    private function findChildNodeConnectedThroughEdgeName(NodeAggregateId $parentNodeAggregateId, NodeName $nodeName): ?Node
    {
        foreach ($this->graphStructure->nodes[$this->contentStreamId->value][$parentNodeAggregateId->value] as $parentNodeRecord) {
            if ($parentNodeRecord->coversDimensionSpacePoint($this->contentStreamId, $this->dimensionSpacePoint)) {
                $relation = $parentNodeRecord->childrenByContentStream[$this->contentStreamId->value]->getHierarchyHyperrelation($this->dimensionSpacePoint);
                if ($relation !== null) {
                    foreach ($relation->children as $childRecord) {
                        if ($childRecord->name?->equals($nodeName)) {
                            return $this->mapNodeRecordToNode($childRecord);
                        }
                    }
                }
            }
        }

        return null;
    }

    public function findSucceedingSiblingNodes(NodeAggregateId $siblingNodeAggregateId, FindSucceedingSiblingNodesFilter $filter): Nodes
    {
        /** @todo apply filters */
        $parentNodeRecord = $this->findParentNodeRecord($siblingNodeAggregateId);
        $siblingFound = false;
        $succeedingSiblingRecords = [];
        if ($parentNodeRecord !== null) {
            $childRelation = $parentNodeRecord->childrenByContentStream[$this->contentStreamId->value]->getHierarchyHyperrelation($this->dimensionSpacePoint);
            foreach ($childRelation?->children ?: [] as $childRecord) {
                if ($childRecord->nodeAggregateId->equals($siblingNodeAggregateId)) {
                    $siblingFound = true;
                    continue;
                }
                if ($siblingFound) {
                    $succeedingSiblingRecords[] = $childRecord;
                }
            }
        } else {
            // this is a root node
            foreach ($this->graphStructure->rootNodes[$this->contentStreamId->value] as $rootNodeRecord) {
                if ($rootNodeRecord->coversDimensionSpacePoint($this->contentStreamId, $this->dimensionSpacePoint)) {
                    if ($rootNodeRecord->nodeAggregateId->equals($siblingNodeAggregateId)) {
                        $siblingFound = true;
                        continue;
                    }
                    if ($siblingFound) {
                        $succeedingSiblingRecords[] = $rootNodeRecord;
                    }
                }
            }
        }
        return $this->mapNodeRecordsToNodes($succeedingSiblingRecords);
    }

    public function findPrecedingSiblingNodes(NodeAggregateId $siblingNodeAggregateId, FindPrecedingSiblingNodesFilter $filter): Nodes
    {
        /** @todo apply filters */
        $parentNodeRecord = $this->findParentNodeRecord($siblingNodeAggregateId);
        $siblingFound = false;
        $precedingSiblingRecords = [];
        if ($parentNodeRecord !== null) {
            $childRelation = $parentNodeRecord->childrenByContentStream[$this->contentStreamId->value]->getHierarchyHyperrelation($this->dimensionSpacePoint);
            foreach ($childRelation?->children->reverse() ?: [] as $childRecord) {
                if ($childRecord->nodeAggregateId->equals($siblingNodeAggregateId)) {
                    $siblingFound = true;
                    continue;
                }
                if ($siblingFound) {
                    $precedingSiblingRecords[] = $childRecord;
                }
            }
        } else {
            // this is a root node
            $rootNodeRecords = InMemoryNodeRecords::create(...$this->graphStructure->rootNodes[$this->contentStreamId->value]);
            $rootNodeRecords = array_reverse(iterator_to_array($rootNodeRecords));
            foreach ($rootNodeRecords as $rootNodeRecord) {
                if ($rootNodeRecord->coversDimensionSpacePoint($this->contentStreamId, $this->dimensionSpacePoint)) {
                    if ($rootNodeRecord->nodeAggregateId->equals($siblingNodeAggregateId)) {
                        $siblingFound = true;
                        continue;
                    }
                    if ($siblingFound) {
                        $precedingSiblingRecords[] = $rootNodeRecord;
                    }
                }
            }
        }
        return $this->mapNodeRecordsToNodes($precedingSiblingRecords);
    }

    public function retrieveNodePath(NodeAggregateId $nodeAggregateId): AbsoluteNodePath
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
    }

    public function findSubtree(NodeAggregateId $entryNodeAggregateId, FindSubtreeFilter $filter): ?Subtree
    {
        $entryNodeRecord = $this->findNodeRecord($entryNodeAggregateId);

        return $entryNodeRecord !== null
            ? $this->nodeFactory->mapNodeRecordToSubtree(
                $entryNodeRecord,
                $this->workspaceName,
                $this->contentStreamId,
                $this->dimensionSpacePoint,
                $this->visibilityConstraints,
                0,
                $filter,
            )
            : null;
    }

    public function findAncestorNodes(NodeAggregateId $entryNodeAggregateId, FindAncestorNodesFilter $filter): Nodes
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
    }

    public function countAncestorNodes(NodeAggregateId $entryNodeAggregateId, CountAncestorNodesFilter $filter): int
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
    }

    public function findClosestNode(NodeAggregateId $entryNodeAggregateId, FindClosestNodeFilter $filter): ?Node
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
    }

    public function findDescendantNodes(NodeAggregateId $entryNodeAggregateId, FindDescendantNodesFilter $filter): Nodes
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
    }

    public function countDescendantNodes(NodeAggregateId $entryNodeAggregateId, CountDescendantNodesFilter $filter): int
    {
        throw new \Exception(__METHOD__ . ' not implemented yet');
    }

    public function countNodes(): int
    {
        $numberOfNodes = 0;
        foreach ($this->graphStructure->nodes[$this->contentStreamId->value] as $nodeRecords) {
            foreach ($nodeRecords as $nodeRecord) {
                if (
                    $nodeRecord->coversDimensionSpacePoint($this->contentStreamId, $this->dimensionSpacePoint)
                ) {
                    $numberOfNodes++;
                }
            }
        }

        return $numberOfNodes;
    }

    /** ------------------------------------------- */

    private function findNodeByPathFromStartingNode(NodePath $path, Node $startingNode): ?Node
    {
        $currentNode = $startingNode;

        foreach ($path->getParts() as $edgeName) {
            $currentNode = $this->findChildNodeConnectedThroughEdgeName($currentNode->aggregateId, $edgeName);
            if ($currentNode === null) {
                return null;
            }
        }
        return $currentNode;
    }

    private function findNodeRecord(NodeAggregateId $nodeAggregateId): ?InMemoryNodeRecord
    {
        $nodeRecords = $this->graphStructure->nodes[$this->contentStreamId->value][$nodeAggregateId->value];
        foreach ($nodeRecords as $nodeRecord) {
            if ($nodeRecord->coversDimensionSpacePoint($this->contentStreamId, $this->dimensionSpacePoint)) {
                return $nodeRecord;
            }
        }

        return null;
    }

    private function findParentNodeRecord(NodeAggregateId $childNodeAggregateId): ?InMemoryNodeRecord
    {
        $nodeRecords = $this->graphStructure->nodes[$this->contentStreamId->value][$childNodeAggregateId->value];
        foreach ($nodeRecords as $nodeRecord) {
            $relation = $nodeRecord->parentsByContentStreamId[$this->contentStreamId->value]->getHierarchyHyperrelation($this->dimensionSpacePoint);
            if ($relation !== null) {
                return $relation->parent;
            }
        }

        return null;
    }

    private function mapNodeRecordToNode(InMemoryNodeRecord $nodeRecord): Node
    {
        return $this->nodeFactory->mapNodeRecordToNode(
            $nodeRecord,
            $this->workspaceName,
            $this->dimensionSpacePoint,
            $this->visibilityConstraints,
        );
    }

    /**
     * @param iterable<int, InMemoryNodeRecord> $nodeRecords
     */
    private function mapNodeRecordsToNodes(iterable $nodeRecords): Nodes
    {
        return $this->nodeFactory->mapNodeRecordsToNodes(
            $nodeRecords,
            $this->workspaceName,
            $this->dimensionSpacePoint,
            $this->visibilityConstraints,
        );
    }

    /**
     * @param array<int,InMemoryReferenceRecord> $referenceRecords
     */
    private function mapReferenceRecordsToReferences(
        array $referenceRecords,
        bool $backwards,
    ): References {
        return $this->nodeFactory->mapReferenceRecordsToReferences(
            $referenceRecords,
            $this->workspaceName,
            $this->dimensionSpacePoint,
            $this->visibilityConstraints,
            $backwards,
        );
    }
}
