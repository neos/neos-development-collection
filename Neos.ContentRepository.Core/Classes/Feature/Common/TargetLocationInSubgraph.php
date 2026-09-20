<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\Common;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindPrecedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindSucceedingSiblingNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateCurrentlyDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeAggregateDoesCurrentlyNotCoverDimensionSpacePoint;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

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
        if ($parentNodeAggregateId) {
            self::requireNodeInSubgraph($parentNodeAggregateId, $dimensionSpacePoint, $contentGraph);
        }
        if ($succeedingSiblingNodeAggregateId) {
            self::requireNodeInSubgraph($succeedingSiblingNodeAggregateId, $dimensionSpacePoint, $contentGraph);
        }
        if ($precedingSiblingNodeAggregateId) {
            self::requireNodeInSubgraph($precedingSiblingNodeAggregateId, $dimensionSpacePoint, $contentGraph);
        }

        return new self(
            parentNodeAggregateId: $parentNodeAggregateId,
            succeedingSiblingNodeAggregateId: $succeedingSiblingNodeAggregateId,
            precedingSiblingNodeAggregateId: $precedingSiblingNodeAggregateId,
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

    public function resolveInterdimensionalSibling(
        ContentSubgraphInterface $subgraph,
        ContentSubgraphInterface $sourceSubgraph
    ): InterdimensionalSibling {
        $succeedingSiblingId = null;
        if ($this->succeedingSiblingNodeAggregateId) {
            if ($subgraph->findNodeById($this->succeedingSiblingNodeAggregateId)) {
                $succeedingSiblingId = $this->succeedingSiblingNodeAggregateId;
            } else {
                foreach (
                    $sourceSubgraph->findSucceedingSiblingNodes(
                        $this->succeedingSiblingNodeAggregateId,
                        FindSucceedingSiblingNodesFilter::create(),
                    ) as $furtherSucceedingSibling
                ) {
                    if ($subgraph->findNodeById($furtherSucceedingSibling->aggregateId)) {
                        $succeedingSiblingId = $furtherSucceedingSibling->aggregateId;
                        break;
                    }
                }
            }
        }
        if (!$succeedingSiblingId && $this->precedingSiblingNodeAggregateId) {
            $succeedingSibling = $subgraph->findSucceedingSiblingNodes(
                $this->precedingSiblingNodeAggregateId,
                FindSucceedingSiblingNodesFilter::create(pagination: Pagination::fromLimitAndOffset(1, 0)),
            )->first();
            if ($succeedingSibling) {
                $succeedingSiblingId = $succeedingSibling->aggregateId;
            } else {
                foreach (
                    $sourceSubgraph->findPrecedingSiblingNodes(
                        $this->precedingSiblingNodeAggregateId,
                        FindPrecedingSiblingNodesFilter::create(),
                    ) as $furtherSourcePrecedingSibling
                ) {
                    $succeedingSibling = $subgraph->findSucceedingSiblingNodes(
                        $furtherSourcePrecedingSibling->aggregateId,
                        FindSucceedingSiblingNodesFilter::create(pagination: Pagination::fromLimitAndOffset(1, 0)),
                    )->first();
                    if ($succeedingSibling) {
                        $succeedingSiblingId = $succeedingSibling->aggregateId;
                        break;
                    }
                }
            }
        }

        return new InterdimensionalSibling(
            $subgraph->getDimensionSpacePoint(),
            $succeedingSiblingId,
        );
    }
}
