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

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\Service\ContentRepositoryBootstrapper;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle;
use Neos\Neos\Domain\Exception\LiveWorkspaceIsMissing;
use Neos\Neos\Domain\Exception\SitesNodeIsMissing;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Model\SiteNodeName;

class SiteServiceInternals implements ContentRepositoryServiceInterface
{
    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly InterDimensionalVariationGraph $interDimensionalVariationGraph,
        private readonly NodeTypeManager $nodeTypeManager
    ) {
    }

    public function removeSiteNode(SiteNodeName $siteNodeName): void
    {
        $dimensionSpacePoints = $this->interDimensionalVariationGraph->getDimensionSpacePoints()->points;
        $arbitraryDimensionSpacePoint = reset($dimensionSpacePoints) ?: null;
        if (!$arbitraryDimensionSpacePoint instanceof DimensionSpacePoint) {
            throw new \InvalidArgumentException(
                'Cannot prune site "' . $siteNodeName->toNodeName()->value
                . '" due to the dimension space being empty',
                1651921482
            );
        }
        $contentGraph = $this->contentRepository->getContentGraph();

        foreach ($this->contentRepository->getContentStreamFinder()->findAllIds() as $contentStreamId) {
            $sitesNodeAggregate = $contentGraph->findRootNodeAggregateByType(
                $contentStreamId,
                NodeTypeName::fromString('Neos.Neos:Sites')
            );
            $siteNodeAggregates = $contentGraph->findChildNodeAggregatesByName(
                $contentStreamId,
                $sitesNodeAggregate->nodeAggregateId,
                $siteNodeName->toNodeName()
            );

            foreach ($siteNodeAggregates as $siteNodeAggregate) {
                assert($siteNodeAggregate instanceof NodeAggregate);
                $this->contentRepository->handle(new RemoveNodeAggregate(
                    $contentStreamId,
                    $siteNodeAggregate->nodeAggregateId,
                    $arbitraryDimensionSpacePoint,
                    NodeVariantSelectionStrategy::STRATEGY_ALL_VARIANTS,
                ));
            }
        }
    }

    public function createSiteNodeIfNotExists(Site $site, string $nodeTypeName): void
    {
        $bootstrapper = ContentRepositoryBootstrapper::create($this->contentRepository);
        $liveContentStreamId = $bootstrapper->getOrCreateLiveContentStream();
        $sitesNodeIdentifier = $bootstrapper->getOrCreateRootNodeAggregate(
            $liveContentStreamId,
            NodeTypeNameFactory::forSites()
        );
        $siteNodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);

        if ($siteNodeType->getName() === 'Neos.Neos:FallbackNode') {
            throw new NodeTypeNotFoundException(
                'Cannot create a site using a non-existing node type.',
                1412372375
            );
        }
        $siteNodeAggregate = $this->contentRepository->getContentGraph()->findChildNodeAggregatesByName(
            $liveContentStreamId,
            $sitesNodeIdentifier,
            $site->getNodeName()->toNodeName(),
        );
        foreach ($siteNodeAggregate as $_) {
            // Site node already exists
            return;
        }

        $rootDimensionSpacePoints = $this->interDimensionalVariationGraph->getRootGeneralizations();
        if (empty($rootDimensionSpacePoints)) {
            throw new \InvalidArgumentException(
                'The dimension space is empty, please check your configuration.',
                1651957153
            );
        }
        $arbitraryRootDimensionSpacePoint = array_shift($rootDimensionSpacePoints);

        $siteNodeAggregateId = NodeAggregateId::create();
        $this->contentRepository->handle(new CreateNodeAggregateWithNode(
            $liveContentStreamId,
            $siteNodeAggregateId,
            NodeTypeName::fromString($nodeTypeName),
            OriginDimensionSpacePoint::fromDimensionSpacePoint($arbitraryRootDimensionSpacePoint),
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
                $liveContentStreamId,
                $siteNodeAggregateId,
                OriginDimensionSpacePoint::fromDimensionSpacePoint($arbitraryRootDimensionSpacePoint),
                OriginDimensionSpacePoint::fromDimensionSpacePoint($rootDimensionSpacePoint),
            ))->block();
        }
    }
}
