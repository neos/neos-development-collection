<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeIdentity;
use Neos\Flow\Annotations as Flow;

/**
 * Utility to convert the {@see Node} to its {@see NodeIdentity} and reverse.
 *
 * @api
 */
#[Flow\Scope('singleton')]
final readonly class NodeSerializer
{
    public function __construct(
        private ContentRepositoryRegistry $contentRepositoryRegistry
    ) {
    }

    public function findNodeByIdentity(NodeIdentity $identity, VisibilityConstraints $visibilityConstraints): Node
    {
        $contentRepository = $this->contentRepositoryRegistry->get($identity->contentRepositoryId);
        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName($identity->workspaceName);
        if (!$workspace) {
            throw new \RuntimeException(sprintf('Workspace could not be found while fetching NodeIdentity<%s>.', json_encode($identity, JSON_PARTIAL_OUTPUT_ON_ERROR)), 1707757488);
        }
        $subgraph = $contentRepository->getContentGraph()->getSubgraph(
            $workspace->currentContentStreamId,
            $identity->dimensionSpacePoint,
            $visibilityConstraints
        );
        $node = $subgraph->findNodeById($identity->nodeAggregateId);
        if (!$node) {
            throw new \RuntimeException(sprintf('NodeAggregateId could not be found while fetching NodeIdentity<%s>.', json_encode($identity, JSON_PARTIAL_OUTPUT_ON_ERROR)), 1707772263);
        }
        return $node;
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
