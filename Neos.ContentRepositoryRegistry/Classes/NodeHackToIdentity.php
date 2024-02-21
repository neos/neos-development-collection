<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry;

use Neos\ContentRepository\Core\Projection\ContentGraph\ContentSubgraphInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeIdentity;
use Neos\Flow\Annotations as Flow;

/**
 * TODO THIS IS JUST TEMPORARY
 */
final class NodeHackToIdentity
{
    /**
     * @Flow\Inject
     */
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    public function getSubgraph(NodeIdentity $identity, VisibilityConstraints $visibilityConstraints): ContentSubgraphInterface
    {
        $contentRepository = $this->contentRepositoryRegistry->get($identity->contentRepositoryId);
        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName($identity->workspaceName);
        if (!$workspace) {
            throw new \RuntimeException(sprintf('Workspace could not be found while fetching NodeIdentity<%s>.', json_encode($identity, JSON_PARTIAL_OUTPUT_ON_ERROR)), 1707757488);
        }
        return $contentRepository->getContentGraph()->getSubgraph(
            $workspace->currentContentStreamId,
            $identity->dimensionSpacePoint,
            $visibilityConstraints
        );
    }

    public function convertNodeToIdentity(Node $node): NodeIdentity
    {
        $contentRepository = $this->contentRepositoryRegistry->get($node->subgraphIdentity->contentRepositoryId);
        $workspace = $contentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamId($node->subgraphIdentity->contentStreamId);
        if (!$workspace) {
            throw new \RuntimeException(sprintf('Workspace could not be found for current content stream %s.', $node->subgraphIdentity->contentStreamId->value), 1707757787);
        }
        return NodeIdentity::create(
            $node->subgraphIdentity->contentRepositoryId,
            $workspace->workspaceName,
            $node->subgraphIdentity->dimensionSpacePoint,
            $node->nodeAggregateId
        );
    }
}
