<?php
namespace Neos\EventSourcedNeosAdjustments\Domain\Projection\Site;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\EventSourcing\Projection\Doctrine\AbstractDoctrineFinder;
use Neos\Flow\Annotations as Flow;


/**
 * Site Finder
 * @Flow\Scope("singleton")
 */
final class SiteFinder extends AbstractDoctrineFinder
{


    /**
     * @Flow\InjectConfiguration(package="Neos.Neos", path="defaultSiteNodeName")
     * @var string
     */
    protected $defaultSiteNodeName;


    /**
     * @param NodeName $nodeName
     * @return Site|null
     */
    public function findOneByNodeName(NodeName $nodeName) : ?Site
    {
        return $this->__call('findOneByNodeName', [(string)$nodeName]);
    }

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
        return $this->findByActive(true);
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
        $defaultSite = $this->findOneByNodeName(new NodeName($this->defaultSiteNodeName));
        if (!$defaultSite instanceof Site || !$defaultSite->active) {
            throw new NeosException(sprintf('DefaultSiteNode %s not found or not active', $this->defaultSiteNodeName), 1476374818);
        }
        return $defaultSite;
    }
}
