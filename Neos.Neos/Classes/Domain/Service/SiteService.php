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

use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Feature\Common\NodeTypeNotFoundException;
use Neos\ContentRepository\Feature\Common\PropertyValuesToWrite;
use Neos\ContentRepository\Feature\NodeAggregateCommandHandler;
use Neos\ContentRepository\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Feature\NodeDisabling\Command\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\NodeAccess\NodeAccessorManager;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\Projection\Content\NodeInterface;
use Neos\ContentRepository\Projection\ContentStream\ContentStreamFinder;
use Neos\ContentRepository\Projection\Workspace\Workspace;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\Asset;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Neos\Domain\Exception\LiveWorkspaceIsMissing;
use Neos\Neos\Domain\Exception\SiteNodeNameIsAlreadyInUseByAnotherSite;
use Neos\Neos\Domain\Exception\SitesNodeIsMissing;
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
    public const SITES_ROOT_PATH = '/sites';

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

    #[Flow\Inject]
    protected ContentGraphInterface $contentGraph;

    #[Flow\Inject]
    protected WorkspaceFinder $workspaceFinder;

    #[Flow\Inject]
    protected NodeTypeManager $nodeTypeManager;

    #[Flow\Inject]
    protected InterDimensionalVariationGraph $variationGraph;

    #[Flow\Inject]
    protected UserService $domainUserService;

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
                $nodeName
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

    public function createSite(
        string $packageKey,
        string $siteName,
        string $nodeTypeName,
        ?string $nodeName = null,
        bool $inactive = false
    ): Site {
        $siteNodeName = NodeName::fromString($nodeName ?: $siteName);
        $liveWorkspace = $this->workspaceFinder->findOneByName(WorkspaceName::forLive());
        if (!$liveWorkspace instanceof Workspace) {
            throw LiveWorkspaceIsMissing::butWasRequested();
        }
        try {
            $sitesNode = $this->contentGraph->findRootNodeAggregateByType(
                $liveWorkspace->getCurrentContentStreamIdentifier(),
                NodeTypeNameFactory::forSites()
            );
        } catch (\Exception $exception) {
            throw SitesNodeIsMissing::butWasRequested();
        }

        $siteNodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);

        if ($siteNodeType->getName() === 'Neos.Neos:FallbackNode') {
            throw new NodeTypeNotFoundException(
                'Cannot create a site using a non-existing node type.',
                1412372375
            );
        }

        if ($this->siteRepository->findOneByNodeName($siteNodeName->jsonSerialize())) {
            throw SiteNodeNameIsAlreadyInUseByAnotherSite::butWasAttemptedToBeClaimed($siteNodeName);
        }

        $rootDimensionSpacePoints = $this->variationGraph->getRootGeneralizations();
        if (empty($rootDimensionSpacePoints)) {
            throw new \InvalidArgumentException(
                'The dimension space is empty, please check your configuration.',
                1651957153
            );
        }
        $arbitraryRootDimensionSpacePoint = array_shift($rootDimensionSpacePoints);

        $currentUserIdentifier = $this->domainUserService->getCurrentUserIdentifier();
        if (is_null($currentUserIdentifier)) {
            $currentUserIdentifier = UserIdentifier::forSystemUser();
        }

        $siteNodeAggregateIdentifier = NodeAggregateIdentifier::create();
        $this->nodeAggregateCommandHandler->handleCreateNodeAggregateWithNode(new CreateNodeAggregateWithNode(
            $liveWorkspace->getCurrentContentStreamIdentifier(),
            $siteNodeAggregateIdentifier,
            NodeTypeName::fromString($nodeTypeName),
            OriginDimensionSpacePoint::fromDimensionSpacePoint($arbitraryRootDimensionSpacePoint),
            $currentUserIdentifier,
            $sitesNode->getIdentifier(),
            null,
            $siteNodeName,
            PropertyValuesToWrite::fromArray([
                'title' => $siteName
            ])
        ))->blockUntilProjectionsAreUpToDate();

        // Handle remaining root dimension space points by creating peer variants
        foreach ($rootDimensionSpacePoints as $rootDimensionSpacePoint) {
            $this->nodeAggregateCommandHandler->handleCreateNodeVariant(new CreateNodeVariant(
                $liveWorkspace->getCurrentContentStreamIdentifier(),
                $siteNodeAggregateIdentifier,
                OriginDimensionSpacePoint::fromDimensionSpacePoint($arbitraryRootDimensionSpacePoint),
                OriginDimensionSpacePoint::fromDimensionSpacePoint($rootDimensionSpacePoint),
                $currentUserIdentifier
            ));
        }

        // @todo use node aggregate identifier instead of node name
        $site = new Site((string)$siteNodeName);
        $site->setSiteResourcesPackageKey($packageKey);
        $site->setState($inactive ? Site::STATE_OFFLINE : Site::STATE_ONLINE);
        $site->setName($siteName);
        $this->siteRepository->add($site);

        return $site;
    }
}
