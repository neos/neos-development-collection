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
     * Converts legacy paths like "/sites/site/absolute/path", "~/site-relative/path" and "~" to the corresponding
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
    public function tryResolveLegacyPathSyntaxToAbsoluteNodePath(
        string $path,
        Node $baseNode
    ): ?AbsoluteNodePath {
        if (str_contains($path, '../') || str_contains($path, './')) {
            throw new \InvalidArgumentException(sprintf('NodePath traversal via /../ is not allowed. Got: "%s"', $path), 1707732065);
        }

        $isSiteRelative = str_starts_with($path, '~');
        $isLegacyAbsolute = str_starts_with($path, '/');

        if ($isLegacyAbsolute && !str_starts_with($path, '/sites/')) {
            throw new \InvalidArgumentException(sprintf('Legacy absolute paths are only supported when starting with "/sites" like "/sites/my-site". Got: "%s"', $path), 1719949067);
        }

        if ($isLegacyAbsolute) {
            $pathWithoutSitesRoot = substr($path, strlen('/sites/'));
            return AbsoluteNodePath::fromRootNodeTypeNameAndRelativePath(
                NodeTypeNameFactory::forSites(),
                NodePath::fromString($pathWithoutSitesRoot)
            );
        }

        if ($isSiteRelative) {
            $subgraph = $this->contentRepositoryRegistry->subgraphForNode($baseNode);

            $siteNode = $subgraph->findClosestNode($baseNode->aggregateId, FindClosestNodeFilter::create(nodeTypes: NodeTypeNameFactory::NAME_SITE));
            if ($siteNode === null) {
                throw new \RuntimeException(sprintf(
                    'Failed to determine site node for aggregate node "%s" in workspace "%s" and dimension "%s"',
                    $baseNode->aggregateId->value,
                    $subgraph->getWorkspaceName()->value,
                    $subgraph->getDimensionSpacePoint()->toJson()
                ), 1601366598);
            }
            if ($siteNode->name === null) {
                throw new \RuntimeException(sprintf(
                    'Site node "%s" does not have a node name',
                    $siteNode->aggregateId->value,
                ), 1719947246);
            }
            return AbsoluteNodePath::fromRootNodeTypeNameAndRelativePath(
                NodeTypeNameFactory::forSites(),
                NodePath::fromPathSegments(
                    [$siteNode->name->value, ...explode('/', substr($path, 1))]
                )
            );
        }

        // not a legacy absolute node path
        return null;
    }
}
