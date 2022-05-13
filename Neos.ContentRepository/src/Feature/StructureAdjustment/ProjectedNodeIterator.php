<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Feature\StructureAdjustment;

use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\Node\ReadableNodeAggregateInterface;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;

/**
 * Low-Level helper service, iterating over the "real" Nodes in the Live workspace; that is, the nodes,
 * which have an entry in the Graph Projection's "node" table.
 * You need to iterate over the nodeAggregates of type, and then call "getNode()" on each aggregate.
 *
 * This is needed for e.g. Structure Adjustments.
 *
 * You should not need this class in your own code.
 */
#[Flow\Scope("singleton")]
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
     * @return ReadableNodeAggregateInterface[]
     */
    public function nodeAggregatesOfType(NodeTypeName $nodeTypeName): iterable
    {
        $contentStreamIdentifier = $this->findLiveContentStream();
        $nodeAggregates = $this->contentGraph->findNodeAggregatesByType($contentStreamIdentifier, $nodeTypeName);
        foreach ($nodeAggregates as $nodeAggregate) {
            yield $nodeAggregate;
        }
    }

    private function findLiveContentStream(): ContentStreamIdentifier
    {
        $liveWorkspace = $this->workspaceFinder->findOneByName(WorkspaceName::forLive());
        assert($liveWorkspace !== null, 'Live workspace not found');

        return $liveWorkspace->getCurrentContentStreamIdentifier();
    }
}
