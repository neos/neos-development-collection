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
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\InterDimensionalVariationGraph;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\Service\ContentRepositoryBootstrapper;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\Neos\Domain\Exception\SiteNodeTypeIsInvalid;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Model\SiteNodeName;

readonly class SiteServiceInternals implements ContentRepositoryServiceInterface
{
    public function __construct(
        private ContentRepository $contentRepository,
        private InterDimensionalVariationGraph $interDimensionalVariationGraph,
        private NodeTypeManager $nodeTypeManager
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

        foreach ($this->contentRepository->getWorkspaceFinder()->findAll() as $workspace) {
            $sitesNodeAggregate = $contentGraph->findRootNodeAggregateByType(
                $workspace->currentContentStreamId,
                NodeTypeNameFactory::forSites()
            );
            $siteNodeAggregates = $contentGraph->findChildNodeAggregatesByName(
                $workspace->currentContentStreamId,
                $sitesNodeAggregate->nodeAggregateId,
                $siteNodeName->toNodeName()
            );

            foreach ($siteNodeAggregates as $siteNodeAggregate) {
                assert($siteNodeAggregate instanceof NodeAggregate);
                $this->contentRepository->handle(RemoveNodeAggregate::create(
                    $workspace->workspaceName,
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
        $liveWorkspace = $bootstrapper->getOrCreateLiveWorkspace();
        $sitesNodeIdentifier = $bootstrapper->getOrCreateRootNodeAggregate(
            $liveWorkspace,
            NodeTypeNameFactory::forSites()
        );
        try {
            $siteNodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);
        } catch (NodeTypeNotFoundException $exception) {
            throw new NodeTypeNotFoundException(
                'Cannot create a site using a non-existing node type.',
                1412372375,
                $exception
            );
        }

        if (!$siteNodeType->isOfType(NodeTypeNameFactory::NAME_SITE)) {
            throw SiteNodeTypeIsInvalid::becauseItIsNotOfTypeSite(NodeTypeName::fromString($nodeTypeName));
        }

        $siteNodeAggregate = $this->contentRepository->getContentGraph()->findChildNodeAggregatesByName(
            $liveWorkspace->currentContentStreamId,
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
        $this->contentRepository->handle(CreateNodeAggregateWithNode::create(
            $liveWorkspace->workspaceName,
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
            $this->contentRepository->handle(CreateNodeVariant::create(
                $liveWorkspace->workspaceName,
                $siteNodeAggregateId,
                OriginDimensionSpacePoint::fromDimensionSpacePoint($arbitraryRootDimensionSpacePoint),
                OriginDimensionSpacePoint::fromDimensionSpacePoint($rootDimensionSpacePoint),
            ))->block();
        }
    }
}
