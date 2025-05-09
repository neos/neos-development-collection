<?php

declare(strict_types=1);

namespace Neos\Neos\Utility;

use Neos\ContentRepository\Core\Projection\ContentGraph\AbsoluteNodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;

/**
 * @internal implementation detail of Neos.Neos:NodeUri
 */
final class NodePathResolver
{
    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * Converts string-like node representations based the base node to the corresponding NodeAddress.
     *
     * Following string syntax is allowed, as well as passing a NodePath or AbsoluteNodePath value object:
     *
     *  - /<Neos.Neos:Sites>/my-site/main
     *  - some/relative/path
     *
     * The node protocol node://my-node-identifier is not handled here.
     *
     * The following legacy syntax is not implemented and handled here:
     *
     *  - /sites/site/absolute/path
     *  - ~/site-relative/path
     *  - ~
     *  - ./neos/info
     *  - ../foo/../../bar
     *
     * For handling partially legacy paths please preprocess the path using {@see LegacyNodePathNormalizer::tryResolveLegacyPathSyntaxToAbsoluteNodePath()}
     */
    public function resolveNodeAddressByPath(AbsoluteNodePath|NodePath|string $path, Node $baseNode): NodeAddress
    {
        if ($path === '') {
            throw new \RuntimeException('Empty strings can not be resolved to nodes.', 1719999872);
        }

        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($baseNode);

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
        $targetNode = $subgraph->findNodeByPath($path, $baseNode->aggregateId);

        if ($targetNode === null) {
            throw new \RuntimeException(sprintf(
                'Node on path "%s" could not be found for base node "%s" in workspace "%s" and dimension %s',
                $path->serializeToString(),
                $baseNode->aggregateId->value,
                $subgraph->getWorkspaceName()->value,
                $subgraph->getDimensionSpacePoint()->toJson()
            ), 1719950342);
        }
        return NodeAddress::fromNode($targetNode);
    }
}
