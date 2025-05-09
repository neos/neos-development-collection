<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */


declare(strict_types=1);

namespace Neos\Neos\Domain\Service;

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;

#[Flow\Scope('singleton')]
final class SiteNodeUtility
{
    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry
    ) {
    }

    /**
     * Find the site node by the neos site entity.
     *
     * To find the site node for the live workspace in a 0 dimensional content repository use:
     *
     * ```php
     * $siteNode = $this->siteNodeUtility->findSiteNodeBySite(
     *     $site,
     *     WorkspaceName::forLive(),
     *     DimensionSpacePoint::createWithoutDimensions()
     * );
     * ```
     *
     * To resolve the Site by a node use {@see SiteRepository::findSiteBySiteNode()}
     */
    public function findSiteNodeBySite(
        Site $site,
        WorkspaceName $workspaceName,
        DimensionSpacePoint $dimensionSpacePoint
    ): Node {
        $contentRepository = $this->contentRepositoryRegistry->get($site->getConfiguration()->contentRepositoryId);

        $subgraph = $contentRepository->getContentSubgraph($workspaceName, $dimensionSpacePoint);

        $rootNode = $subgraph->findRootNodeByType(NodeTypeNameFactory::forSites());

        if (!$rootNode) {
            throw new \RuntimeException(sprintf('No sites root node found in content repository "%s", while fetching site node "%s"', $contentRepository->id->value, $site->getNodeName()), 1719046570);
        }

        $siteNode = $subgraph->findNodeByPath(
            $site->getNodeName()->toNodeName(),
            $rootNode->aggregateId
        );

        if (!$siteNode) {
            throw new \RuntimeException(sprintf('No site node found for site "%s"', $site->getNodeName()), 1697140379);
        }

        if (!$contentRepository->getNodeTypeManager()->getNodeType($siteNode->nodeTypeName)?->isOfType(NodeTypeNameFactory::NAME_SITE)) {
            throw new \RuntimeException(sprintf(
                'The site node "%s" (type: "%s") must be of type "%s"',
                $siteNode->aggregateId->value,
                $siteNode->nodeTypeName->value,
                NodeTypeNameFactory::NAME_SITE
            ), 1697140367);
        }

        return $siteNode;
    }
}
