<?php

declare(strict_types=1);

namespace Neos\ContentRepository\StructureAdjustment\Adjustment;

use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

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
    protected WorkspaceFinder $workspaceFinder;
    protected ContentGraphInterface $contentGraph;

    public function __construct(WorkspaceFinder $workspaceFinder, ContentGraphInterface $contentGraph)
    {
        $this->workspaceFinder = $workspaceFinder;
        $this->contentGraph = $contentGraph;
    }

    /**
     * @param NodeTypeName $nodeTypeName
     * @return NodeAggregate[]
     */
    public function nodeAggregatesOfType(NodeTypeName $nodeTypeName): iterable
    {
        $contentStreamId = $this->findLiveContentStream();
        $nodeAggregates = $this->contentGraph->findNodeAggregatesByType($contentStreamId, $nodeTypeName);
        foreach ($nodeAggregates as $nodeAggregate) {
            yield $nodeAggregate;
        }
    }

    private function findLiveContentStream(): ContentStreamId
    {
        $liveWorkspace = $this->workspaceFinder->findOneByName(WorkspaceName::forLive());
        assert($liveWorkspace !== null, 'Live workspace not found');

        return $liveWorkspace->currentContentStreamId;
    }
}
