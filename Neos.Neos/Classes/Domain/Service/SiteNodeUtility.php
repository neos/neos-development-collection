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
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Utility\NodeTypeWithFallbackProvider;

#[Flow\Scope('singleton')]
final class SiteNodeUtility
{
    use NodeTypeWithFallbackProvider;

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
     *     DimensionSpacePoint::createWithoutDimensions(),
     *     VisibilityConstraints::frontend()
     * );
     * ```
     *
     * To resolve the Site by a node use {@see SiteRepository::findSiteBySiteNode()}
     */
    public function findSiteNodeBySite(
        Site $site,
        WorkspaceName $workspaceName,
        DimensionSpacePoint $dimensionSpacePoint,
        VisibilityConstraints $visibilityConstraints
    ): Node {
        $contentRepository = $this->contentRepositoryRegistry->get($site->getConfiguration()->contentRepositoryId);

        $contentGraph = $contentRepository->getContentGraph($workspaceName);
        $subgraph = $contentGraph->getSubgraph(
            $dimensionSpacePoint,
            $visibilityConstraints,
        );

        $rootNodeAggregate = $contentGraph->findRootNodeAggregateByType(
            NodeTypeNameFactory::forSites()
        );
        $rootNode = $rootNodeAggregate->getNodeByCoveredDimensionSpacePoint($dimensionSpacePoint);

        $siteNode = $subgraph->findNodeByPath(
            $site->getNodeName()->toNodeName(),
            $rootNode->aggregateId
        );

        if (!$siteNode) {
            throw new \RuntimeException(sprintf('No site node found for site "%s"', $site->getNodeName()), 1697140379);
        }

        if (!$this->getNodeType($siteNode)->isOfType(NodeTypeNameFactory::NAME_SITE)) {
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
