<?php

declare(strict_types=1);

namespace Neos\Neos\Controller\Frontend;

use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSubtreeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\Subtree;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\SubgraphCachingInMemory\ContentSubgraphWithRuntimeCaches;
use Neos\ContentRepositoryRegistry\SubgraphCachingInMemory\SubgraphCachePool;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;

/**
 * Cache warmup for rendering
 *
 * When rendering a document its child content nodes are regularly traversed.
 * This is done conventionally via fusion and flow query traversing the children
 * via {@see ContentSubgraphInterface::findChildNodes()} recursively.
 *
 * To optimise rendering we fetch the subtree, excluding nested document nodes, effectively all child content nodes
 * of the entry document and use those to fill the cache.
 *
 * By ensuring we use {@see ContentRepositoryRegistry::subgraphForNode()} in flow queries, we access the cached subgraph
 * via {@see ContentSubgraphWithRuntimeCaches}
 *
 * Reference loading of nodes is not optimised.
 *
 * TODO: Add tests that caches are filled correctly
 * TODO: Add performance tests as that the findSubtree() CTE is indeed faster, than multiple child node queries.
 *
 * @internal implementation detail of the Node controller
 */
class ContentSubgraphCacheWarmup
{
    #[Flow\Inject]
    protected SubgraphCachePool $subgraphCachePool;

    public function fillCacheWithContentNodes(
        NodeAggregateId $entryDocumentNodeAggregateId,
        ContentSubgraphInterface $subgraph,
    ): void {
        $subtree = $subgraph->findSubtree(
            $entryDocumentNodeAggregateId,
            FindSubtreeFilter::create(nodeTypes: '!' . NodeTypeNameFactory::NAME_DOCUMENT, maximumLevels: 20)
        );
        if ($subtree === null) {
            return;
        }

        $currentDocumentNode = $subtree->node;

        foreach ($subtree->children as $childSubtree) {
            $this->fillCacheInternal(
                $childSubtree,
                $currentDocumentNode,
                $subgraph
            );
        }
    }

    private function fillCacheInternal(
        Subtree $subtree,
        Node $parentNode,
        ContentSubgraphInterface $subgraph,
    ): void {
        $node = $subtree->node;

        $parentNodeIdentifierByChildNodeIdentifierCache
            = $this->subgraphCachePool->getParentNodeIdByChildNodeIdCache($subgraph);
        $namedChildNodeByNodeIdentifierCache = $this->subgraphCachePool->getNamedChildNodeByNodeIdCache($subgraph);
        $allChildNodesByNodeIdentifierCache = $this->subgraphCachePool->getAllChildNodesByNodeIdCache($subgraph);
        if ($node->name !== null) {
            $namedChildNodeByNodeIdentifierCache->add(
                $parentNode->aggregateId,
                $node->name,
                $node
            );
        } else {
            // @todo use node aggregate identifier instead?
        }

        $parentNodeIdentifierByChildNodeIdentifierCache->add(
            $node->aggregateId,
            $parentNode->aggregateId
        );

        $allChildNodes = [];
        foreach ($subtree->children as $childSubtree) {
            $this->fillCacheInternal($childSubtree, $node, $subgraph);
            $childNode = $childSubtree->node;
            $allChildNodes[] = $childNode;
        }
        // TODO Explain why this is safe (Content can not contain other documents)
        $allChildNodesByNodeIdentifierCache->add(
            $node->aggregateId,
            null,
            Nodes::fromArray($allChildNodes)
        );
    }
}
