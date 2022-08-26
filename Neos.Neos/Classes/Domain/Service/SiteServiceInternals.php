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

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\DimensionSpace\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Feature\Common\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Feature\Common\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Feature\Common\PropertyValuesToWrite;
use Neos\ContentRepository\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Projection\Workspace\Workspace;
use Neos\ContentRepository\Service\ContentRepositoryBootstrapper;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeManager;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceTitle;
use Neos\Neos\Domain\Exception\LiveWorkspaceIsMissing;
use Neos\Neos\Domain\Exception\SitesNodeIsMissing;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Model\SiteNodeName;

class SiteServiceInternals implements ContentRepositoryServiceInterface
{
    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly ContentDimensionZookeeper $contentDimensionZookeeper,
        private readonly InterDimensionalVariationGraph $interDimensionalVariationGraph,
        private readonly NodeTypeManager $nodeTypeManager
    ) {
    }

    public function removeSiteNode(SiteNodeName $siteNodeName): void
    {
        $dimensionSpacePoints = $this->contentDimensionZookeeper->getAllowedDimensionSubspace()->points;
        $arbitraryDimensionSpacePoint = reset($dimensionSpacePoints) ?: null;
        if (!$arbitraryDimensionSpacePoint instanceof DimensionSpacePoint) {
            throw new \InvalidArgumentException(
                'Cannot prune site "' . $siteNodeName->toNodeName()
                . '" due to the dimension space being empty',
                1651921482
            );
        }
        $contentGraph = $this->contentRepository->getContentGraph();

        foreach ($this->contentRepository->getContentStreamFinder()->findAllIdentifiers() as $contentStreamIdentifier) {
            $sitesNodeAggregate = $contentGraph->findRootNodeAggregateByType(
                $contentStreamIdentifier,
                NodeTypeName::fromString('Neos.Neos:Sites')
            );
            $siteNodeAggregates = $contentGraph->findChildNodeAggregatesByName(
                $contentStreamIdentifier,
                $sitesNodeAggregate->getIdentifier(),
                $siteNodeName->toNodeName()
            );

            foreach ($siteNodeAggregates as $siteNodeAggregate) {
                assert($siteNodeAggregate instanceof NodeAggregate);
                $this->contentRepository->handle(new RemoveNodeAggregate(
                    $contentStreamIdentifier,
                    $siteNodeAggregate->getIdentifier(),
                    $arbitraryDimensionSpacePoint,
                    NodeVariantSelectionStrategy::STRATEGY_ALL_VARIANTS,
                    UserIdentifier::forSystemUser()
                ));
            }
        }
    }

    public function createSiteNode(Site $site, string $nodeTypeName, UserIdentifier $currentUserIdentifier): void
    {
        $bootstrapper = ContentRepositoryBootstrapper::create($this->contentRepository);
        $liveContentStreamIdentifier = $bootstrapper->getOrCreateLiveContentStream();
        $sitesNodeIdentifier = $bootstrapper->getOrCreateRootNodeAggregate(
            $liveContentStreamIdentifier,
            NodeTypeNameFactory::forSites()
        );
        $siteNodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);

        if ($siteNodeType->getName() === 'Neos.Neos:FallbackNode') {
            throw new NodeTypeNotFoundException(
                'Cannot create a site using a non-existing node type.',
                1412372375
            );
        }

        $rootDimensionSpacePoints = $this->interDimensionalVariationGraph->getRootGeneralizations();
        if (empty($rootDimensionSpacePoints)) {
            throw new \InvalidArgumentException(
                'The dimension space is empty, please check your configuration.',
                1651957153
            );
        }
        $arbitraryRootDimensionSpacePoint = array_shift($rootDimensionSpacePoints);

        $siteNodeAggregateIdentifier = NodeAggregateIdentifier::create();
        $this->contentRepository->handle(new CreateNodeAggregateWithNode(
            $liveContentStreamIdentifier,
            $siteNodeAggregateIdentifier,
            NodeTypeName::fromString($nodeTypeName),
            OriginDimensionSpacePoint::fromDimensionSpacePoint($arbitraryRootDimensionSpacePoint),
            $currentUserIdentifier,
            $sitesNodeIdentifier,
            null,
            $site->getNodeName()->toNodeName(),
            PropertyValuesToWrite::fromArray([
                'title' => $site->getName()
            ])
        ))->block();

        // Handle remaining root dimension space points by creating peer variants
        foreach ($rootDimensionSpacePoints as $rootDimensionSpacePoint) {
            $this->contentRepository->handle(new CreateNodeVariant(
                $liveContentStreamIdentifier,
                $siteNodeAggregateIdentifier,
                OriginDimensionSpacePoint::fromDimensionSpacePoint($arbitraryRootDimensionSpacePoint),
                OriginDimensionSpacePoint::fromDimensionSpacePoint($rootDimensionSpacePoint),
                $currentUserIdentifier
            ))->block();
        }
    }
}
