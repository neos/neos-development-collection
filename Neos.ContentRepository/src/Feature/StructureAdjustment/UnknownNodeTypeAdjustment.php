<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\StructureAdjustment;

use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\EventSourcing\EventStore\EventStore;

#[Flow\Scope('singleton')]
class UnknownNodeTypeAdjustment
{
    use RemoveNodeAggregateTrait;
    use LoadNodeTypeTrait;

    protected EventStore $eventStore;
    protected ProjectedNodeIterator $projectedNodeIterator;
    protected NodeTypeManager $nodeTypeManager;
    protected ReadSideMemoryCacheManager $readSideMemoryCacheManager;
    protected RuntimeBlocker $runtimeBlocker;

    public function __construct(
        EventStore $eventStore,
        ProjectedNodeIterator $projectedNodeIterator,
        NodeTypeManager $nodeTypeManager,
        ReadSideMemoryCacheManager $readSideMemoryCacheManager,
        RuntimeBlocker $runtimeBlocker
    ) {
        $this->eventStore = $eventStore;
        $this->projectedNodeIterator = $projectedNodeIterator;
        $this->nodeTypeManager = $nodeTypeManager;
        $this->readSideMemoryCacheManager = $readSideMemoryCacheManager;
        $this->runtimeBlocker = $runtimeBlocker;
    }

    public function getRuntimeBlocker(): RuntimeBlocker
    {
        return $this->runtimeBlocker;
    }

    /**
     * @return \Generator<int,StructureAdjustment>
     */
    public function findAdjustmentsForNodeType(NodeTypeName $nodeTypeName): \Generator
    {
        $nodeType = $this->loadNodeType($nodeTypeName);
        if ($nodeType === null) {
            // node type is not existing right now.
            yield from $this->removeAllNodesOfType($nodeTypeName);
        }
    }

    protected function getEventStore(): EventStore
    {
        return $this->eventStore;
    }

    /**
     * @return \Generator<int,StructureAdjustment>
     */
    private function removeAllNodesOfType(NodeTypeName $nodeTypeName): \Generator
    {
        foreach ($this->projectedNodeIterator->nodeAggregatesOfType($nodeTypeName) as $nodeAggregate) {
            yield StructureAdjustment::createForNodeAggregate(
                $nodeAggregate,
                StructureAdjustment::NODE_TYPE_MISSING,
                'The node type "' . $nodeTypeName->jsonSerialize()
                    . '" is not found; so the node should be removed (or converted)',
                function () use ($nodeAggregate) {
                    $this->readSideMemoryCacheManager->disableCache();
                    return $this->removeNodeAggregate($nodeAggregate);
                }
            );
        }
    }

    protected function getNodeTypeManager(): NodeTypeManager
    {
        return $this->nodeTypeManager;
    }
}
