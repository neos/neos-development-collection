<?php

declare(strict_types=1);

namespace Neos\Neos\Utility;

use Neos\ContentRepository\Core\Projection\ContentGraph\AbsoluteNodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;

final class NodeAddressNormalizer
{
    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * Converts strings like "relative/path", ...
     * to the corresponding NodeAddress
     *
     * For handling legacy paths like "/sites/site/absolute/path", "~/site-relative/path" and "~",
     * please combine with {@see LegacyNodePathNormalizer::tryResolveLegacyPathSyntaxToAbsoluteNodePath()}
     */
    public function resolveNodeAddressFromPath(AbsoluteNodePath|NodePath|string $path, Node $documentNode): NodeAddress
    {
        if ($path === '') {
            throw new \RuntimeException('Empty strings can not be resolved to nodes.', 1719999872);
        }

        if (is_string($path) && str_starts_with($path, 'node://')) {
            return NodeAddress::fromNode($documentNode)->withAggregateId(
                NodeAggregateId::fromString(substr($path, strlen('node://')))
            );
        }

        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($documentNode);

        if (is_string($path) && AbsoluteNodePath::patternIsMatchedByString($path)) {
            $path = AbsoluteNodePath::fromString($path);
        }
        if ($path instanceof AbsoluteNodePath) {
            $targetNode = $subgraph->findNodeByAbsolutePath($path);
            if ($targetNode === null) {
                throw new \RuntimeException(sprintf(
                    'Node on absolute path "%s" could not be found in workspace "%s" and dimension %s',
                    $path->serializeToString(),
                    $subgraph->getWorkspaceName()->value,
                    $subgraph->getDimensionSpacePoint()->toJson()
                ), 1719950354);
            }
            return NodeAddress::fromNode($targetNode);
        }

        if (is_string($path)) {
            $path = NodePath::fromString($path);
        }
        $targetNode = $subgraph->findNodeByPath($path, $documentNode->aggregateId);

        if ($targetNode === null) {
            throw new \RuntimeException(sprintf(
                'Node on path "%s" could not be found for base node "%s" in workspace "%s" and dimension %s',
                $path->serializeToString(),
                $documentNode->aggregateId->value,
                $subgraph->getWorkspaceName()->value,
                $subgraph->getDimensionSpacePoint()->toJson()
            ), 1719950342);
        }
        return NodeAddress::fromNode($targetNode);
    }
}
