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

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Neos\Domain\Exception\SiteNodeNameIsAlreadyInUseByAnotherSite;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;

/**
 * A service for manipulating sites
 *
 * @Flow\Scope("singleton")
 */
class SiteService
{
    /**
     * @Flow\Inject
     * @var DomainRepository
     */
    protected $domainRepository;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var AssetCollectionRepository
     */
    protected $assetCollectionRepository;

    /**
     * Remove given site all nodes for that site and all domains associated.
     */
    public function pruneSite(Site $site): void
    {
        $siteServiceInternals = $this->contentRepositoryRegistry->buildService(
            $site->getConfiguration()->contentRepositoryId,
            new SiteServiceInternalsFactory()
        );

        try {
            $siteServiceInternals->removeSiteNode($site->getNodeName());
        } catch (\Doctrine\DBAL\Exception $exception) {
            throw new \RuntimeException(sprintf(
                'Could not remove site nodes for site "%s", please ensure the content repository is setup.',
                $site->getName()
            ), 1707302419, $exception);
        }

        $site->setPrimaryDomain(null);
        $this->siteRepository->update($site);

        $domainsForSite = $this->domainRepository->findBySite($site);
        foreach ($domainsForSite as $domain) {
            $this->domainRepository->remove($domain);
        }
        $this->persistenceManager->persistAll();

        $this->siteRepository->remove($site);

        $this->emitSitePruned($site);
    }



    /**
     * Remove all sites and their respective nodes and domains
     *
     * @return void
     */
    public function pruneAll()
    {
        foreach ($this->siteRepository->findAll() as $site) {
            $this->pruneSite($site);
        }
    }

    /**
     * Adds an asset to the asset collection of the site it has been uploaded to
     * Note: This is usually triggered by the ContentController::assetUploaded signal
     *
     * @param Asset $asset
     * @param Node $node
     * @param string $propertyName
     * @return void
     */
    public function assignUploadedAssetToSiteAssetCollection(Asset $asset, Node $node, string $propertyName)
    {
        $subgraph = $this->contentRepositoryRegistry->subgraphForNode($node);
        $siteNode = $subgraph->findClosestNode($node->aggregateId, FindClosestNodeFilter::create(nodeTypes: NodeTypeNameFactory::NAME_SITE));
        if (!$siteNode) {
            // should not happen
            return;
        }
        if ($siteNode->nodeName === null) {
            return;
        }
        $site = $this->siteRepository->findOneByNodeName($siteNode->nodeName->value);
        if ($site === null) {
            return;
        }
        $assetCollection = $site->getAssetCollection();
        if ($assetCollection === null) {
            return;
        }
        $assetCollection->addAsset($asset);
        $this->assetCollectionRepository->update($assetCollection);
    }

    /**
     * Signal that is triggered whenever a site has been pruned
     *
     * @Flow\Signal
     * @param Site $site The site that was pruned
     * @return void
     */
    protected function emitSitePruned(Site $site)
    {
    }

    public function createSite(
        string $packageKey,
        string $siteName,
        string $nodeTypeName,
        ?string $nodeName = null,
        bool $inactive = false
    ): Site {
        $siteNodeName = NodeName::fromString($nodeName ?: $siteName);

        if ($this->siteRepository->findOneByNodeName($siteNodeName->value)) {
            throw SiteNodeNameIsAlreadyInUseByAnotherSite::butWasAttemptedToBeClaimed($siteNodeName);
        }

        // @todo use node aggregate identifier instead of node name
        $site = new Site($siteNodeName->value);
        $site->setSiteResourcesPackageKey($packageKey);
        $site->setState($inactive ? Site::STATE_OFFLINE : Site::STATE_ONLINE);
        $site->setName($siteName);
        $this->siteRepository->add($site);

        $siteServiceInternals = $this->contentRepositoryRegistry->buildService(
            $site->getConfiguration()->contentRepositoryId,
            new SiteServiceInternalsFactory()
        );
        $siteServiceInternals->createSiteNodeIfNotExists($site, $nodeTypeName);

        return $site;
    }
}
