<?php

declare(strict_types=1);

namespace Neos\ContentRepository\StructureAdjustment\Adjustment;

use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceFinder;

/**
 * Low-Level helper service, iterating over the "real" Nodes in the Live workspace; that is, the nodes,
 * which have an entry in the Graph Projection's "node" table.
 * You need to iterate over the nodeAggregates of type, and then call "getNode()" on each aggregate.
 *
 * This is needed for e.g. Structure Adjustments.
 *
 * You should not need this class in your own code.
 */
class ProjectedNodeIterator
{
    public function __construct(
        public readonly ContentGraphInterface $contentGraph
    )
    {
    }

    /**
     * @param NodeTypeName $nodeTypeName
     * @return NodeAggregate[]
     */
    public function nodeAggregatesOfType(NodeTypeName $nodeTypeName): iterable
    {
        $nodeAggregates = $this->contentGraph->findNodeAggregatesByType($nodeTypeName);
        foreach ($nodeAggregates as $nodeAggregate) {
            yield $nodeAggregate;
        }
    }
}
