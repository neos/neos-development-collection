<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\Common;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsNoChild;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateIsNoSibling;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateIds;

/**
 * A structurally valid target location of a node in a subgraph, meaning
 *  * the parent, if given, exists
 *  * the succeeding sibling, if given, exists and is a child of the parent if given or a sibling of the node if not
 *  * the preceding sibling, if given, exists and is a child of the parent if given or a sibling of the node if not
 *
 * Does not evaluate node type constraints
 *
 * @internal to be used by command handlers
 */
final readonly class TargetLocationInSubgraph
{
    private function __construct(
        public NodeAggregateId $nodeAggregateId,
        public ?NodeAggregateId $parentNodeAggregateId,
        public ?NodeAggregateId $succeedingSiblingNodeAggregateId,
        public ?NodeAggregateId $precedingSiblingNodeAggregateId,
    ) {
    }

    public static function create(
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePoint $dimensionSpacePoint,
        ?NodeAggregateId $parentNodeAggregateId,
        ?NodeAggregateId $succeedingSiblingNodeAggregateId,
        ?NodeAggregateId $precedingSiblingNodeAggregateId,
        ContentGraphInterface $contentGraph,
    ): self {
        $subgraph = $contentGraph->getSubgraph($dimensionSpacePoint, VisibilityConstraints::createEmpty());
        if ($parentNodeAggregateId) {
            if ($parentNodeAggregateId->equals($nodeAggregateId)) {
                throw new \InvalidArgumentException('Cannot target node as its own parent', 1789940221);
            }
            self::requireNodeInSubgraph($parentNodeAggregateId, $dimensionSpacePoint, $contentGraph);
        }
        if ($succeedingSiblingNodeAggregateId) {
            if ($succeedingSiblingNodeAggregateId->equals($nodeAggregateId)) {
                throw new \InvalidArgumentException('Cannot target node as its own succeeding sibling', 1789940239);
            }
            self::requireNodeInSubgraph($succeedingSiblingNodeAggregateId, $dimensionSpacePoint, $contentGraph);
            if ($parentNodeAggregateId) {
                self::requireNodeToBeChild($succeedingSiblingNodeAggregateId, $parentNodeAggregateId, $subgraph);
            } else {
                self::requireNodeToBeSibling($nodeAggregateId, $succeedingSiblingNodeAggregateId, $subgraph);
            }
        }
        if ($precedingSiblingNodeAggregateId) {
            if ($precedingSiblingNodeAggregateId->equals($nodeAggregateId)) {
                throw new \InvalidArgumentException('Cannot target node as its own preceding sibling', 1789940256);
            }
            self::requireNodeInSubgraph($precedingSiblingNodeAggregateId, $dimensionSpacePoint, $contentGraph);
            if ($parentNodeAggregateId) {
                self::requireNodeToBeChild($precedingSiblingNodeAggregateId, $parentNodeAggregateId, $subgraph);
            } else {
                self::requireNodeToBeSibling($nodeAggregateId, $precedingSiblingNodeAggregateId, $subgraph);
            }
        }

        return new self(
            nodeAggregateId: $nodeAggregateId,
            parentNodeAggregateId: $parentNodeAggregateId,
            succeedingSiblingNodeAggregateId: $succeedingSiblingNodeAggregateId,
            precedingSiblingNodeAggregateId: $precedingSiblingNodeAggregateId,
        );
    }

    public static function createForTetheredChildNodeAggregate(
        NodeAggregateId $parentNodeAggregateId,
        NodeAggregateId $tetheredChildNodeAggregateId,
        ContentSubgraphInterface $sourceSubgraph,
    ): self {
        return new self(
            nodeAggregateId: $tetheredChildNodeAggregateId,
            parentNodeAggregateId: $parentNodeAggregateId,
            succeedingSiblingNodeAggregateId: $sourceSubgraph->findSucceedingSiblingNodes(
                $tetheredChildNodeAggregateId,
                FindSucceedingSiblingNodesFilter::create(
                    pagination: Pagination::fromLimitAndOffset(1, 0)
                )
            )->first()?->aggregateId,
            precedingSiblingNodeAggregateId: $sourceSubgraph->findPrecedingSiblingNodes(
                $tetheredChildNodeAggregateId,
                FindPrecedingSiblingNodesFilter::create(
                    pagination: Pagination::fromLimitAndOffset(1, 0)
                )
            )->first()?->aggregateId,
        );
    }

    private static function requireNodeInSubgraph(
        NodeAggregateId $nodeAggregateId,
        DimensionSpacePoint $dimensionSpacePoint,
        ContentGraphInterface $contentGraph,
    ): void {
        $nodeAggregate = $contentGraph->findNodeAggregateById($nodeAggregateId);
        if (!$nodeAggregate) {
            throw NodeAggregateCurrentlyDoesNotExist::butWasExpectedTo($nodeAggregateId);
        }
        if (!$contentGraph->getSubgraph($dimensionSpacePoint, VisibilityConstraints::createEmpty())->findNodeById($nodeAggregateId)) {
            throw NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint::butWasSupposedTo(
                $nodeAggregateId,
                $dimensionSpacePoint,
            );
        }
    }

    /**
     * @throws NodeAggregateIsNoChild
     */
    private static function requireNodeToBeChild(
        NodeAggregateId $nodeAggregateId,
        NodeAggregateId $parentNodeAggregateId,
        ContentSubgraphInterface $subgraph,
    ): void {
        if (
            $subgraph->findParentNode($nodeAggregateId)?->aggregateId->value
            !== $parentNodeAggregateId->value
        ) {
            throw NodeAggregateIsNoChild::butWasExpectedToBeInDimensionSpacePoint(
                $nodeAggregateId,
                $parentNodeAggregateId,
                $subgraph->getDimensionSpacePoint(),
            );
        }
    }

    /**
     * @throws NodeAggregateIsNoSibling
     */
    private static function requireNodeToBeSibling(
        NodeAggregateId $nodeAggregateId,
        NodeAggregateId $siblingNodeAggregateId,
        ContentSubgraphInterface $subgraph,
    ): void {
        if (
            $subgraph->findParentNode($nodeAggregateId)?->aggregateId->value
            !== $subgraph->findParentNode($siblingNodeAggregateId)?->aggregateId->value
        ) {
            throw NodeAggregateIsNoSibling::butWasExpectedToBeInDimensionSpacePoint(
                $siblingNodeAggregateId,
                $nodeAggregateId,
                $subgraph->getDimensionSpacePoint(),
            );
        }
    }

    public function tryResolveInterdimensionalSibling(
        ContentSubgraphInterface $targetSubgraph,
        ContentSubgraphInterface $sourceSubgraph,
        bool $allowNullSibling,
    ): ?InterdimensionalSibling {
        $succeedingSiblingId = null;
        if ($this->succeedingSiblingNodeAggregateId) {
            if ($this->isSibling($this->succeedingSiblingNodeAggregateId, $targetSubgraph)) {
                $succeedingSiblingId = $this->succeedingSiblingNodeAggregateId;
            } else {
                foreach (
                    $sourceSubgraph->findSucceedingSiblingNodes(
                        $this->succeedingSiblingNodeAggregateId,
                        FindSucceedingSiblingNodesFilter::create(),
                    ) as $furtherSucceedingSiblingInSource
                ) {
                    if ($this->isSibling($furtherSucceedingSiblingInSource->aggregateId, $targetSubgraph)) {
                        $succeedingSiblingId = $furtherSucceedingSiblingInSource->aggregateId;
                        break;
                    }
                }
            }
        }
        if (!$succeedingSiblingId && $this->precedingSiblingNodeAggregateId) {
            $sourcePrecedingSiblings = $sourceSubgraph->findPrecedingSiblingNodes(
                $this->precedingSiblingNodeAggregateId,
                FindPrecedingSiblingNodesFilter::create(),
            );
            $precedingSiblingIds = NodeAggregateIds::fromArray([$this->precedingSiblingNodeAggregateId])
                ->merge($sourcePrecedingSiblings->toNodeAggregateIds());

            foreach ($precedingSiblingIds as $precedingSiblingId) {
                if ($this->isSibling($precedingSiblingId, $targetSubgraph, true)) {
                    // one of the source preceding siblings is also a sibling in the target subgraph,
                    // so if no succeeding sibling can be resolved we explicitly select the end of the sibling list
                    $allowNullSibling = true;

                    $succeedingSibling = $targetSubgraph->findSucceedingSiblingNodes(
                        $precedingSiblingId,
                        FindSucceedingSiblingNodesFilter::create(pagination: Pagination::fromLimitAndOffset(1, 0)),
                    )->first();
                    if (
                        $succeedingSibling
                        && $this->isSiblingNode($succeedingSibling, $targetSubgraph)
                        && !$precedingSiblingIds->contain($succeedingSibling->aggregateId)
                    ) {
                        $succeedingSiblingId = $succeedingSibling->aggregateId;
                        break;
                    }
                }
            }
        }

        return $succeedingSiblingId || $allowNullSibling
            ? new InterdimensionalSibling(
                $targetSubgraph->getDimensionSpacePoint(),
                $succeedingSiblingId,
            )
            : null;
    }

    private function isSibling(NodeAggregateId $siblingCandidate, ContentSubgraphInterface $subgraph, bool $includeSelf = false): bool
    {
        return (
            ($siblingNode = $subgraph->findNodeById($siblingCandidate))
            && $this->isSiblingNode($siblingNode, $subgraph, $includeSelf)
        );
    }

    private function isSiblingNode(Node $siblingCandidate, ContentSubgraphInterface $subgraph, bool $includeSelf = false): bool
    {
        $parentNodeAggregateId = $this->parentNodeAggregateId
            ?: $subgraph->findParentNode($this->nodeAggregateId)?->aggregateId;
        // root node aggregates can neither be moved, varied or created under another parent
        assert($parentNodeAggregateId instanceof NodeAggregateId);
        return ($includeSelf || !$siblingCandidate->aggregateId->equals($this->nodeAggregateId))
            && $subgraph->findNodeById($siblingCandidate->aggregateId)
            && $subgraph->findParentNode($siblingCandidate->aggregateId)
                ?->aggregateId->equals($parentNodeAggregateId);
    }
}
