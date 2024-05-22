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

namespace Neos\Neos\Domain\Repository;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Flow\Persistence\QueryResultInterface;
use Neos\Flow\Persistence\Repository;
use Neos\Neos\Domain\Exception as NeosException;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Model\SiteNodeName;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\Domain\Service\SiteNodeUtility;
use Neos\Neos\Utility\NodeTypeWithFallbackProvider;

/**
 * The Site Repository
 *
 * @Flow\Scope("singleton")
 * @api
 * @method QueryResultInterface|Site[] findByNodeName(string $nodeName)
 * @method QueryResultInterface|Site[] findByState(int $state)
 */
class SiteRepository extends Repository
{
    use NodeTypeWithFallbackProvider;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * @var array<string,string>
     */
    protected $defaultOrderings = [
        'name' => QueryInterface::ORDER_ASCENDING,
        'nodeName' => QueryInterface::ORDER_ASCENDING
    ];

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
    public function findFirst(): ?Site
    {
        /** @var ?Site $result */
        $result = $this->createQuery()->execute()->getFirst();

        return $result;
    }

    /**
     * Find all sites with status "online"
     *
     * @return QueryResultInterface<Site>
     */
    public function findOnline(): QueryResultInterface
    {
        return $this->findByState(Site::STATE_ONLINE);
    }

    /**
     * Find first site with status "online"
     */
    public function findFirstOnline(): ?Site
    {
        /** @var ?Site $site */
        $site = $this->findOnline()->getFirst();

        return $site;
    }

    public function findOneByNodeName(string|SiteNodeName $nodeName): ?Site
    {
        $query = $this->createQuery();
        /** @var ?Site $site */
        $site = $query->matching(
            $query->equals('nodeName', $nodeName)
        )
            ->execute()
            ->getFirst();

        return $site;
    }

    /**
     * Finds a given site by site node.
     *
     * To find the correct site node by its descended child node leverage `findClosestNode`:
     * ```php
     * $siteNode = $subgraph->findClosestNode(
     *     $node->nodeAggregateId,
     *     FindClosestNodeFilter::create(nodeTypes: NodeTypeNameFactory::NAME_SITE)
     * );
     * ```
     *
     * To resolve the SiteNode by a Site use {@see SiteNodeUtility::findSiteNodeBySite()}
     *
     * @throws \Neos\Neos\Domain\Exception in case the passed $siteNode is not a real site node or no site matches this site node.
     */
    public function findSiteBySiteNode(Node $siteNode): Site
    {
        if (!$this->getNodeType($siteNode)->isOfType(NodeTypeNameFactory::NAME_SITE)) {
            throw new \Neos\Neos\Domain\Exception(sprintf('Node %s is not a site node. Site nodes must be of type "%s".', $siteNode->aggregateId->value, NodeTypeNameFactory::NAME_SITE), 1697108987);
        }
        if ($siteNode->name === null) {
            throw new \Neos\Neos\Domain\Exception(sprintf('Site node "%s" is unnamed', $siteNode->aggregateId->value), 1681286146);
        }
        return $this->findOneByNodeName(SiteNodeName::fromNodeName($siteNode->name))
            ?? throw new \Neos\Neos\Domain\Exception(sprintf('No site found for nodeNodeName "%s"', $siteNode->name->value), 1677245517);
    }

    /**
     * Find the site that was specified in the configuration ``defaultSiteNodeName``
     *
     * If the defaultSiteNodeName-setting is null the first active site is returned
     * If the site is not found or not active an exception is thrown
     *
     * @throws NeosException
     */
    public function findDefault(): ?Site
    {
        if ($this->defaultSiteNodeName === null) {
            return $this->findFirstOnline();
        }

        $defaultSite = $this->findOneByNodeName($this->defaultSiteNodeName);
        if (!$defaultSite instanceof Site || $defaultSite->getState() !== Site::STATE_ONLINE) {
            throw new NeosException(sprintf(
                'DefaultSiteNode %s not found or not active',
                $this->defaultSiteNodeName
            ), 1476374818);
        }
        return $defaultSite;
    }
}
