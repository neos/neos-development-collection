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

use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindBackReferencesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\QueryInterface;
use Neos\Neos\Domain\Exception as NeosException;
use Neos\Neos\Domain\Model\Domain;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Model\SiteConfiguration;
use Neos\Neos\Domain\Model\SiteNodeName;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\Domain\Service\SiteNodeUtility;
use Neos\Neos\Utility\NodeTypeWithFallbackProvider;

/**
 * The Site Repository
 *
 * @Flow\Scope("singleton")
 * @api
 */
class SiteRepository
{
    use NodeTypeWithFallbackProvider;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    /**
     * @Flow\InjectConfiguration(path="sites")
     * @var array
     * @phpstan-var array<string,array<string,mixed>>
     */
    protected $sitesConfiguration = [];

    /**
     * @Flow\InjectConfiguration(package="Neos.Neos", path="defaultSiteNodeName")
     * @var string
     */
    protected $defaultSiteNodeName;

    public function findByDomain(Domain $domain): ?Site
    {
        $cr = $this->contentRepositoryRegistry->get($domain->domainNodeAggregate->getContentRepositoryId());
        $liveWorkspace = $cr->getWorkspaceFinder()->findOneByName(WorkspaceName::forLive());

        $rootGeneralizations = $cr->getVariationGraph()->getRootGeneralizations();
        $arbitraryDimensionSpacePoint = reset($rootGeneralizations);

        $subgraph = $cr->getContentGraph()->getSubgraph(
            $liveWorkspace->currentContentStreamId,
            $arbitraryDimensionSpacePoint,
            VisibilityConstraints::frontend()
        );

        $siteReferences = $subgraph->findBackReferences(
            $domain->domainNodeAggregate->nodeAggregateId,
            FindBackReferencesFilter::create(
                nodeTypes: NodeTypeNameFactory::NAME_SITE,
                pagination: Pagination::fromLimitAndOffset(1, 0)
            )
        );

        if (count($siteReferences) < 1) {
            return null;
        }

        return Site::fromSiteNodeAggregate(
            $cr->getContentGraph()->findNodeAggregateById(
                $liveWorkspace->currentContentStreamId,
                $siteReferences->getNodes()->first()->nodeAggregateId
            )
        );
    }

    public function findOnline(): iterable
    {
        return $this->findAll();
    }

    public function findAll(): iterable
    {
        $cr = $this->contentRepositoryRegistry->get(ContentRepositoryId::fromString('default'));

        $liveWorkspace = $cr->getWorkspaceFinder()->findOneByName(WorkspaceName::forLive());

        $sitesNodeAggregate = $cr->getContentGraph()->findRootNodeAggregateByType(
            $liveWorkspace->currentContentStreamId,
            NodeTypeNameFactory::forSites()
        );

        $sites = $cr->getContentGraph()->findChildNodeAggregates(
            $liveWorkspace->currentContentStreamId,
            $sitesNodeAggregate->nodeAggregateId
        );

        $legacySites = [];

        foreach ($sites as $site) {
            $legacySites[] = Site::fromSiteNodeAggregate($site);
        }

        return $legacySites;
    }

    /**
     * Finds the first site
     *
     * @return Site The first site or NULL if none exists
     * @api
     */
    public function findFirst(): ?Site
    {
        foreach ($this->findAll() as $site) {
            return $site;
        }
        return null;
    }

    /**
     * Find first site with status "online"
     */
    public function findFirstOnline(): ?Site
    {
        return $this->findFirst();
    }

    public function findOneByNodeName(string|SiteNodeName $nodeName): ?Site
    {
        $config = SiteConfiguration::fromArray($this->sitesConfiguration[is_string($nodeName) ? $nodeName : $nodeName->value] ?? $this->sitesConfiguration['*']);

        $cr = $this->contentRepositoryRegistry->get($config->contentRepositoryId);

        $liveWorkspace = $cr->getWorkspaceFinder()->findOneByName(WorkspaceName::forLive());

        $sitesNodeAggregate = $cr->getContentGraph()->findRootNodeAggregateByType(
            $liveWorkspace->currentContentStreamId,
            NodeTypeNameFactory::forSites()
        );

        $sites = $cr->getContentGraph()->findChildNodeAggregatesByName(
            $liveWorkspace->currentContentStreamId,
            $sitesNodeAggregate->nodeAggregateId,
            NodeName::fromString(is_string($nodeName) ? $nodeName : $nodeName->value)
        );

        foreach ($sites as $site) {
            break;
        }

        /** @var NodeAggregate $site */
        return Site::fromSiteNodeAggregate($site);
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
            throw new \Neos\Neos\Domain\Exception(sprintf('Node %s is not a site node. Site nodes must be of type "%s".', $siteNode->nodeAggregateId->value, NodeTypeNameFactory::NAME_SITE), 1697108987);
        }
        if ($siteNode->nodeName === null) {
            throw new \Neos\Neos\Domain\Exception(sprintf('Site node "%s" is unnamed', $siteNode->nodeAggregateId->value), 1681286146);
        }
        return $this->findOneByNodeName(SiteNodeName::fromNodeName($siteNode->nodeName))
            ?? throw new \Neos\Neos\Domain\Exception(sprintf('No site found for nodeNodeName "%s"', $siteNode->nodeName->value), 1677245517);
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
