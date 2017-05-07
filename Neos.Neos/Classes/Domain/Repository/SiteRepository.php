<?php
namespace Neos\Neos\Domain\Repository;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Utility\NodePaths;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Flow\Persistence\Repository;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Exception as NeosException;
use Neos\Neos\Domain\Service\SiteService;

/**
 * The Site Repository
 *
 * @Flow\Scope("singleton")
 * @api
 */
class SiteRepository extends Repository
{

    /**
     * @Flow\InjectConfiguration(package="Neos.Neos", path="defaultSiteNodeName")
     * @var string
     */
    protected $defaultSiteNodeName;

    /**
     * Finds the first site
     *
     * @return Site The first site or NULL if none exists
     * @api
     */
    public function findFirst()
    {
        return $this->createQuery()->execute()->getFirst();
    }

    /**
     * Find all sites with status "online"
     *
     * @return QueryResultInterface
     */
    public function findOnline()
    {
        return $this->findByState(Site::STATE_ONLINE);
    }

    /**
     * Find first site with status "online"
     *
     * @return Site
     */
    public function findFirstOnline()
    {
        return $this->findOnline()->getFirst();
    }

    /**
     * Find the site that was specified in the configuration ``defaultSiteNodeName``
     *
     * If the defaultSiteNodeName-setting is null the first active site is returned
     * If the site is not found or not active an exception is thrown
     *
     * @return Site
     * @throws NeosException
     */
    public function findDefault()
    {
        if ($this->defaultSiteNodeName === null) {
            return $this->findOnline()->getFirst();
        }
        /**
         * @var Site $defaultSite
         */
        $defaultSite = $this->findOneByNodeName($this->defaultSiteNodeName);
        if (!$defaultSite instanceof Site || $defaultSite->getState() !== Site::STATE_ONLINE) {
            throw new NeosException(sprintf('DefaultSiteNode %s not found or not active', $this->defaultSiteNodeName), 1476374818);
        }
        return $defaultSite;
    }

    /**
     * Find the matching site for a given Node
     *
     * @param string $nodePath
     * @return Site
     */
    public function findOneByNodePath($nodePath)
    {
        if (!NodePaths::isSubPathOf(SiteService::SITES_ROOT_PATH, $nodePath)) {
            return null;
        }

        $nodePathParts = explode('/', $nodePath);
        if (!isset($nodePathParts[2])) {
            return null;
        }

        $siteNodeName = $nodePathParts[2];

        return $this->findOneByNodeName($siteNodeName);
    }
}
