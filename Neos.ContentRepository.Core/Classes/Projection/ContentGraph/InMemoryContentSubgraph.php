<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindAncestorNodesFilter;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * An in-memory content subgraph
 */
final class InMemoryContentSubgraph implements ContentSubgraphInterface
{
    /**
     * Nodes, indexed by aggregate id
     * @var array<string,Node>
     */
    protected array $nodeIndex = [];

    /**
     * Root nodes, indexed by node type name
     * @var array<string,Node>
     */
    protected array $rootNodes;

    /**
     * Parent nodes, indexed by child aggregate id
     * @var array<string,Node>
     */
    protected array $parentNodes = [];

    /**
     * Child nodes, sorted internally and indexed by parent aggregate id
     * @var array<string,array<int,Node>>
     */
    protected array $childNodes = [];

    /**
     * References, sorted internally and indexed by referencing node aggregate id
     * @var array<string,array<int,Reference>
     */
    protected array $references = [];

    /**
     * Back references, sorted internally and indexed by referenced node aggregate id
     * @var array<string,array<int,Reference>
     */
    protected array $backReferences = [];

    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly WorkspaceName $workspaceName,
        private readonly DimensionSpacePoint $dimensionSpacePoint,
        private readonly VisibilityConstraints $visibilityConstraints
    ) {
    }

    public function registerRootNode(Node $rootNode): void
    {
        $this->rootNodes[$rootNode->nodeTypeName->value] = $rootNode;
        $this->nodeIndex[$rootNode->aggregateId->value] = $rootNode;
        $this->childNodes[$rootNode->aggregateId->value] = [];
    }

    public function registerNode(
        Node $node,
        NodeAggregateId $parentNodeAggregateId,
        ?NodeAggregateId $succeedingSiblingNodeAggregateId
    ): void {
        $this->nodeIndex[$node->aggregateId->value] = $node;
        $this->childNodes[$node->aggregateId->value] = [];
        $this->parentNodes[$node->aggregateId->value] = $this->nodeIndex[$parentNodeAggregateId->value];
        if ($succeedingSiblingNodeAggregateId) {
            $siblings = $this->childNodes[$parentNodeAggregateId->value];
            $newSiblings = [];
            $nodeInserted = false;
            foreach ($siblings as $sibling) {
                if ($sibling->aggregateId->equals($succeedingSiblingNodeAggregateId)) {
                    $newSiblings[] = $node;
                    $nodeInserted = true;
                }
                $newSiblings[] = $sibling;
            }
            if (!$nodeInserted) {
                $newSiblings[] = $node;
            }

            $this->childNodes[$parentNodeAggregateId->value] = $newSiblings;
        } else {
            $this->childNodes[$parentNodeAggregateId->value][] = $node;
        }
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

    public function findNodeById(NodeAggregateId $nodeAggregateId): ?Node
    {
        return $this->nodeIndex[$nodeAggregateId->value] ?? null;
    }

    public function findRootNodeByType(NodeTypeName $nodeTypeName): ?Node
    {
        return $this->rootNodes[$nodeTypeName->value] ?? null;
    }

    public function findChildNodes(NodeAggregateId $parentNodeAggregateId, Filter\FindChildNodesFilter $filter): Nodes
    {
        // @todo apply filter
        return Nodes::fromArray($this->childNodes[$parentNodeAggregateId->value] ?? []);
    }

    public function countChildNodes(NodeAggregateId $parentNodeAggregateId, Filter\CountChildNodesFilter $filter): int
    {
        // @todo apply filter
        return count($this->childNodes[$parentNodeAggregateId->value]);
    }

    public function findParentNode(NodeAggregateId $childNodeAggregateId): ?Node
    {
        return $this->parentNodes[$childNodeAggregateId->value] ?? null;
    }

    public function findSucceedingSiblingNodes(
        NodeAggregateId $siblingNodeAggregateId,
        Filter\FindSucceedingSiblingNodesFilter $filter
    ): Nodes {
        $succeedingSiblings = [];
        $parentNode = $this->parentNodes[$siblingNodeAggregateId->value] ?? null;
        if (!$parentNode) {
            return Nodes::createEmpty();
        }
        $givenSiblingPassed = false;
        foreach ($this->childNodes[$parentNode->aggregateId->value] ?? [] as $siblingNode) {
            if ($givenSiblingPassed) {
                /** @todo: evaluate filters */
                $succeedingSiblings[] = $siblingNode;
            }
            if ($siblingNode->aggregateId->equals($siblingNodeAggregateId)) {
                $givenSiblingPassed = true;
            }
        }

        return Nodes::fromArray($succeedingSiblings);
    }

    public function findPrecedingSiblingNodes(
        NodeAggregateId $siblingNodeAggregateId,
        Filter\FindPrecedingSiblingNodesFilter $filter
    ): Nodes {
        $precedingSiblings = [];
        $parentNode = $this->parentNodes[$siblingNodeAggregateId->value] ?? null;
        if (!$parentNode) {
            return Nodes::createEmpty();
        }
        $givenSiblingPassed = false;
        foreach ($this->childNodes[$parentNode->aggregateId->value] ?? [] as $siblingNode) {
            if ($siblingNode->aggregateId->equals($siblingNodeAggregateId)) {
                $givenSiblingPassed = true;
            }
            if (!$givenSiblingPassed) {
                /** @todo: evaluate filters */
                $precedingSiblings[] = $siblingNode;
            }
        }

        return Nodes::fromArray($precedingSiblings);
    }

    public function findAncestorNodes(
        NodeAggregateId $entryNodeAggregateId,
        Filter\FindAncestorNodesFilter $filter
    ): Nodes {
        $ancestorNodes = [];
        $ancestorNode = $this->parentNodes[$entryNodeAggregateId->value];
        while ($ancestorNode) {
            /** @todo evaluate filters */
            $ancestorNodes[] = $ancestorNode;
        }

        return Nodes::fromArray($ancestorNodes);
    }

    public function countAncestorNodes(
        NodeAggregateId $entryNodeAggregateId,
        Filter\CountAncestorNodesFilter $filter
    ): int {
        $ancestorsCount = 0;
        $ancestorNode = $this->parentNodes[$entryNodeAggregateId->value];
        while ($ancestorNode) {
            /** @todo evaluate filters */
            $ancestorsCount++;
        }

        return $ancestorsCount;
    }

    public function findClosestNode(NodeAggregateId $entryNodeAggregateId, Filter\FindClosestNodeFilter $filter): ?Node
    {
        /** @todo evaluate filters */
        return $this->nodeIndex[$entryNodeAggregateId->value] ?? null;
    }

    public function findDescendantNodes(
        NodeAggregateId $entryNodeAggregateId,
        Filter\FindDescendantNodesFilter $filter
    ): Nodes {
        return $this->findDescendantsRecursively($entryNodeAggregateId, $filter);
    }

    private function findDescendantsRecursively(
        NodeAggregateId $ancestorNodeAggregateId,
        Filter\FindDescendantNodesFilter $filter,
    ): Nodes {
        /** @todo apply filters */
        $descendants = Nodes::fromArray($this->childNodes[$ancestorNodeAggregateId->value] ?? []);
        foreach ($descendants as $child) {
            $descendants = $descendants->merge(
                $this->findDescendantsRecursively(
                    $child->aggregateId,
                    $filter
                )
            );
        }

        return $descendants;
    }

    public function countDescendantNodes(
        NodeAggregateId $entryNodeAggregateId,
        Filter\CountDescendantNodesFilter $filter
    ): int {
        return $this->countDescendantsRecursively($entryNodeAggregateId, $filter);
    }

    private function countDescendantsRecursively(
        NodeAggregateId $ancestorNodeAggregateId,
        Filter\CountDescendantNodesFilter $filter,
    ): int {
        /** @todo apply filters */
        $numberOfDescendants = count($this->childNodes[$ancestorNodeAggregateId->value] ?? []);
        foreach ($this->childNodes[$ancestorNodeAggregateId->value] ?? [] as $child) {
            $numberOfDescendants += $this->countDescendantsRecursively($child->aggregateId, $filter);
        }

        return $numberOfDescendants;
    }

    public function findSubtree(NodeAggregateId $entryNodeAggregateId, Filter\FindSubtreeFilter $filter): ?Subtree
    {
        $entryNode = $this->nodeIndex[$entryNodeAggregateId->value] ?? null;
        if ($entryNode) {
            /** @todo evaluate filter and apply recursion */
            return new Subtree(0, $entryNode, []);
        }

        return null;
    }

    public function findReferences(NodeAggregateId $nodeAggregateId, Filter\FindReferencesFilter $filter): References
    {
        /** @todo apply filter */
        return References::fromArray($this->references[$nodeAggregateId->value] ?? []);
    }

    public function countReferences(NodeAggregateId $nodeAggregateId, Filter\CountReferencesFilter $filter): int
    {
        /** @todo apply filter */
        return count($this->references[$nodeAggregateId->value] ?? []);
    }

    public function findBackReferences(
        NodeAggregateId $nodeAggregateId,
        Filter\FindBackReferencesFilter $filter
    ): References {
        /** @todo apply filter */
        return References::fromArray($this->backReferences[$nodeAggregateId->value] ?? []);
    }

    public function countBackReferences(NodeAggregateId $nodeAggregateId, Filter\CountBackReferencesFilter $filter): int
    {
        /** @todo apply filter */
        return count($this->backReferences[$nodeAggregateId->value] ?? []);
    }

    public function findNodeByPath(NodeName|NodePath $path, NodeAggregateId $startingNodeAggregateId): ?Node
    {
        $nodeNames = $path instanceof NodeName
            ? [$path]
            : $path->getParts();

        $descendant = null;
        $descendantId = $startingNodeAggregateId;
        foreach ($nodeNames as $nodeName) {
            foreach ($this->childNodes[$descendantId->value] ?? [] as $childNode) {
                if ($childNode->name?->equals($nodeName)) {
                    $descendant = $childNode;
                    break;
                }
            }

            if (!$descendant) {
                return null;
            }
            $descendantId = $descendant->aggregateId;
        }

        return $descendant;
    }

    public function findNodeByAbsolutePath(AbsoluteNodePath $path): ?Node
    {
        $rootNode = $this->rootNodes[$path->rootNodeTypeName->value] ?? null;
        if (!$rootNode) {
            return null;
        }

        return $this->findNodeByPath($path->path, $rootNode->aggregateId);
    }

    public function retrieveNodePath(NodeAggregateId $nodeAggregateId): AbsoluteNodePath
    {
        $leafNode = $this->findNodeById($nodeAggregateId);
        if (!$leafNode) {
            throw new \InvalidArgumentException(
                'Failed to retrieve node path for node "' . $nodeAggregateId->value . '"',
                1687513836
            );
        }

        $ancestors = $this->findAncestorNodes($leafNode->aggregateId, FindAncestorNodesFilter::create())
            ->reverse();

        try {
            return AbsoluteNodePath::fromLeafNodeAndAncestors($leafNode, $ancestors);
        } catch (\InvalidArgumentException $exception) {
            throw new \InvalidArgumentException(
                'Failed to retrieve node path for node "' . $nodeAggregateId->value . '"',
                1687513836,
                $exception
            );
        }
    }

    public function countNodes(): int
    {
        return count($this->nodeIndex);
    }
}
