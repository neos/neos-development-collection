<?php

declare(strict_types=1);

namespace Neos\Neos\Utility;

use Neos\ContentRepository\Core\Projection\ContentGraph\AbsoluteNodePath;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;

/**
 * @deprecated please use the new {@see AbsoluteNodePath} syntax instead.
 */
final class LegacyNodePathNormalizer
{
    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * Converts legacy paths like "/absolute/path", "~/site-relative/path" and "~" to the corresponding
     * AbsoluteNodePath depending on the passed base node.
     *
     * The following syntax is not implemented and handled here:
     *
     *  - node://
     *  - /<Neos.Neos:Sites>/my-site/main
     *  - some/relative/path
     *
     * Also while legacy and previously allowed, node path traversal like ./neos/info or ../foo/../../bar is not handled.
     */
    public function resolveLegacyPathSyntaxToAbsoluteNodePath(
        string $path,
        Node $baseNode
    ): ?AbsoluteNodePath {
        if (str_contains($path, '../') || str_contains($path, './')) {
            throw new \InvalidArgumentException(sprintf('NodePath traversal via /../ is not allowed. Got: "%s"', $path), 1707732065);
        }

        if (!str_starts_with($path, '~') && !str_starts_with($path, '/')) {
            return null;
        }

        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($baseNode);

        $siteNode = $subgraph->findClosestNode($baseNode->nodeAggregateId, FindClosestNodeFilter::create(nodeTypes: NodeTypeNameFactory::NAME_SITE));
        if ($siteNode === null) {
            throw new \RuntimeException(sprintf(
                'Failed to determine site node for aggregate node "%s" and subgraph "%s"',
                $baseNode->nodeAggregateId->value,
                json_encode($subgraph, JSON_PARTIAL_OUTPUT_ON_ERROR)
            ), 1601366598);
        }
        if ($path === '~') {
            return AbsoluteNodePath::fromRootNodeTypeNameAndRelativePath(
                NodeTypeNameFactory::forSites(),
                NodePath::fromNodeNames($siteNode->nodeName)
            );
        } else {
            return AbsoluteNodePath::fromRootNodeTypeNameAndRelativePath(
                NodeTypeNameFactory::forSites(),
                NodePath::fromPathSegments(
                    [$siteNode->nodeName->value, ...explode('/', substr($path, 1))]
                )
            );
        }
    }
}
