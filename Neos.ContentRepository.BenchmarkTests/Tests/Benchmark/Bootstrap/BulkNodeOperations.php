<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BenchmarkTests\Tests\Benchmark\Bootstrap;

use Behat\Step\When;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

trait BulkNodeOperations
{
    #[When('I create descendants of node :parentNodeAggregateId of type :nodeTypeName and depth :depth and breadth :breadth as sample :sampleName')]
    public function createDescendants(string $parentNodeAggregateId, string $nodeTypeName, int $depth, int $breadth, string $sampleName): void
    {
        $now = microtime(true);
        $this->createDescendantNodes(
            parentNodeAggregateId: NodeAggregateId::fromString($parentNodeAggregateId),
            nodeTypeName: NodeTypeName::fromString($nodeTypeName),
            depth: $depth,
            breadth: $breadth,
            currentDepth: 1,
        );
        $this->samples[$sampleName] = new BenchmarkSample((int)((microtime(true) - $now) * 1000));
    }

    private function createDescendantNodes(NodeAggregateId $parentNodeAggregateId, NodeTypeName $nodeTypeName, int $depth, int $breadth, int $currentDepth): void
    {
        for ($i = 1; $i <= $breadth; $i++) {
            $nodeAggregateId = NodeAggregateId::fromString($parentNodeAggregateId . '-' . $i);
            $this->currentContentRepository->handle(
                CreateNodeAggregateWithNode::create(
                    workspaceName: $this->currentWorkspaceName,
                    nodeAggregateId: $nodeAggregateId,
                    nodeTypeName: $nodeTypeName,
                    originDimensionSpacePoint: OriginDimensionSpacePoint::fromDimensionSpacePoint($this->currentDimensionSpacePoint),
                    parentNodeAggregateId: $parentNodeAggregateId,
                )
            );
            if ($currentDepth < $depth) {
                $this->createDescendantNodes(
                    parentNodeAggregateId: $nodeAggregateId,
                    nodeTypeName: $nodeTypeName,
                    depth: $depth,
                    breadth: $breadth,
                    currentDepth: $currentDepth + 1,
                );
            }
        }
    }
}
