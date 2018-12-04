<?php
namespace Neos\Neos\Domain\Service;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Repository\DomainRepository;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\ContentRepository\Domain\Repository\NodeDataRepository;
use Neos\ContentRepository\Domain\Repository\WorkspaceRepository;
use Neos\ContentRepository\Domain\Utility\NodePaths;

/**
 * A service for manipulating sites
 *
 * @Flow\Scope("singleton")
 */
class SiteService
{
    /**
     * This is the node path of the root for all sites in neos.
     */
    const SITES_ROOT_PATH = '/sites';

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

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
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

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
     *
     * @param Site $site
     * @return void
     */
    public function pruneSite(Site $site)
    {
        $siteNodePath = NodePaths::addNodePathSegment(static::SITES_ROOT_PATH, $site->getNodeName());
        $this->nodeDataRepository->removeAllInPath($siteNodePath);
        $siteNodes = $this->nodeDataRepository->findByPath($siteNodePath);
        foreach ($siteNodes as $siteNode) {
            $this->nodeDataRepository->remove($siteNode);
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
     * @param NodeInterface $node
     * @param string $propertyName
     * @return void
     */
    public function assignUploadedAssetToSiteAssetCollection(Asset $asset, NodeInterface $node, string $propertyName)
    {
        $contentContext = $node->getContext();
        if (!$contentContext instanceof ContentContext) {
            return;
        }
        $site = $contentContext->getCurrentSite();
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
}
