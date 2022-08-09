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

use Neos\ContentRepository\Projection\ContentGraph\NodeInterface;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepository\Factory\ContentRepositoryIdentifier;
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

    #[Flow\Inject]
    protected SiteNodeUtility $siteNodeUtility;

    #[Flow\Inject]
    protected UserService $domainUserService;

    /**
     * Remove given site all nodes for that site and all domains associated.
     */
    public function pruneSite(Site $site): void
    {
        $contentRepositoryIdentifier = ContentRepositoryIdentifier::fromString(
            $site->getConfiguration()['contentRepository']
            ?? throw new \RuntimeException(
                'There is no content repository identifier configured in Sites configuration in Settings.yaml:'
                . ' Neos.Neos.sites.*.contentRepository'
            )
        );
        $siteServiceInternals = $this->contentRepositoryRegistry->getService(
            $contentRepositoryIdentifier,
            new SiteServiceInternalsFactory()
        );
        $siteServiceInternals->removeSiteNode($site->getNodeName());

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
        try {
            $siteNode = $this->siteNodeUtility->findSiteNode($node);
        } catch (\InvalidArgumentException $exception) {
            return;
        }

        $site = $this->siteRepository->findOneByNodeName((string)$siteNode->getNodeName());
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

        if ($this->siteRepository->findOneByNodeName($siteNodeName->jsonSerialize())) {
            throw SiteNodeNameIsAlreadyInUseByAnotherSite::butWasAttemptedToBeClaimed($siteNodeName);
        }

        // @todo use node aggregate identifier instead of node name
        $site = new Site((string)$siteNodeName);
        $site->setSiteResourcesPackageKey($packageKey);
        $site->setState($inactive ? Site::STATE_OFFLINE : Site::STATE_ONLINE);
        $site->setName($siteName);
        $this->siteRepository->add($site);

        return $site; // TODO: FIX ME (CODE BELOW)
        $currentUserIdentifier = $this->domainUserService->getCurrentUserIdentifier();
        if (is_null($currentUserIdentifier)) {
            $currentUserIdentifier = UserIdentifier::forSystemUser();
        }

        $contentRepositoryIdentifier = ContentRepositoryIdentifier::fromString(
            $site->getConfiguration()['contentRepository']
            ?? throw new \RuntimeException(
                'There is no content repository identifier configured in Sites configuration in Settings.yaml:'
                . ' Neos.Neos.sites.*.contentRepository'
            )
        );
        $siteServiceInternals = $this->contentRepositoryRegistry->getService(
            $contentRepositoryIdentifier,
            new SiteServiceInternalsFactory()
        );
        $siteServiceInternals->createSiteNode($site, $nodeTypeName, $currentUserIdentifier);

        return $site;
    }
}
