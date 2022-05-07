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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Feature\NodeDisabling\Command\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Projection\ContentStream\ContentStreamFinder;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
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
     * This is the node path of the root for all sites in neos.
     */
    const SITES_ROOT_PATH = '/sites';

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
     * @var WorkspaceFinder
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

    #[Flow\Inject]
    protected NodeAccessorManager $nodeAccessorManager;

    #[Flow\Inject]
    protected ContentDimensionZookeeper $contentDimensionZookeeper;

    #[Flow\Inject]
    protected ContentStreamFinder $contentStreamFinder;

    #[Flow\Inject]
    protected NodeAggregateCommandHandler $nodeAggregateCommandHandler;

    #[Flow\Inject]
    protected SiteNodeUtility $siteNodeUtility;

    /**
     * Remove given site all nodes for that site and all domains associated.
     */
    public function pruneSite(Site $site): void
    {
        $this->removeSiteNode(NodeName::fromString($site->getNodeName()));
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

    private function removeSiteNode(NodeName $nodeName): void
    {
        $dimensionSpacePoints = $this->contentDimensionZookeeper->getAllowedDimensionSubspace()->points;
        $arbitraryDimensionSpacePoint = reset($dimensionSpacePoints) ?: null;
        if (!$arbitraryDimensionSpacePoint instanceof DimensionSpacePoint) {
            throw new \InvalidArgumentException(
                'Cannot prune site "' . $nodeName . '" due to the dimension space being empty',
                1651921482
            );
        }
        foreach ($this->contentStreamFinder->findAllIdentifiers() as $contentStreamIdentifier) {
            $nodeAccessor = $this->nodeAccessorManager->accessorFor(
                $contentStreamIdentifier,
                $arbitraryDimensionSpacePoint,
                VisibilityConstraints::withoutRestrictions()
            );
            try {
                $sitesNode = $nodeAccessor->findRootNodeByType(NodeTypeName::fromString('Neos.Neos:Sites'));
            } catch (\InvalidArgumentException) {
                // no sites node, so nothing to do
                continue;
            }
            $siteNode = $nodeAccessor->findChildNodeConnectedThroughEdgeName(
                $sitesNode,
                NodeName::fromString($nodeName)
            );
            if (!$siteNode) {
                // no site node, so nothing to do
                continue;
            }

            $this->nodeAggregateCommandHandler->handleRemoveNodeAggregate(new RemoveNodeAggregate(
                $contentStreamIdentifier,
                $siteNode->getNodeAggregateIdentifier(),
                $arbitraryDimensionSpacePoint,
                NodeVariantSelectionStrategy::STRATEGY_ALL_VARIANTS,
                UserIdentifier::forSystemUser()
            ));
        }
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
}
