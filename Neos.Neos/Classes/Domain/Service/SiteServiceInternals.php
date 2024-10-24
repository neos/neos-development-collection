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
use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\PropertyValuesToWrite;
use Neos\ContentRepository\Core\Feature\NodeRemoval\Command\RemoveNodeAggregate;
use Neos\ContentRepository\Core\Feature\NodeVariation\Command\CreateNodeVariant;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Command\CreateRootNodeAggregateWithNode;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFound;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeVariantSelectionStrategy;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Neos\Domain\Exception\SiteNodeTypeIsInvalid;
use Neos\Neos\Domain\Model\Site;
use Neos\Neos\Domain\Model\SiteNodeName;

/**
 * @internal FIXME refactor and incorporate into SiteService
 */
readonly class SiteServiceInternals
{
    private NodeTypeManager $nodeTypeManager;

    private InterDimensionalVariationGraph $interDimensionalVariationGraph;

    public function __construct(
        private ContentRepository $contentRepository,
        private WorkspaceService $workspaceService,
    ) {
        $this->nodeTypeManager = $this->contentRepository->getNodeTypeManager();
        $this->interDimensionalVariationGraph = $this->contentRepository->getVariationGraph();
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

        foreach ($this->contentRepository->findWorkspaces() as $workspace) {
            $contentGraph = $this->contentRepository->getContentGraph($workspace->workspaceName);
            $sitesNodeAggregate = $contentGraph->findRootNodeAggregateByType(
                NodeTypeNameFactory::forSites()
            );
            if (!$sitesNodeAggregate) {
                // nothing to prune, we could probably also return here directly?
                continue;
            }
            $siteNodeAggregate = $contentGraph->findChildNodeAggregateByName(
                $sitesNodeAggregate->nodeAggregateId,
                $siteNodeName->toNodeName()
            );
            if ($siteNodeAggregate instanceof NodeAggregate) {
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
        $this->workspaceService->createLiveWorkspaceIfMissing($this->contentRepository->id);

        $sitesNodeIdentifier = $this->getOrCreateRootNodeAggregate();
        $siteNodeType = $this->nodeTypeManager->getNodeType($nodeTypeName);
        if (!$siteNodeType) {
            throw new NodeTypeNotFound(
                'Cannot create a site using a non-existing node type.',
                1412372375
            );
        }

        if (!$siteNodeType->isOfType(NodeTypeNameFactory::NAME_SITE)) {
            throw SiteNodeTypeIsInvalid::becauseItIsNotOfTypeSite(NodeTypeName::fromString($nodeTypeName));
        }

        $siteNodeAggregate = $this->contentRepository->getContentGraph(WorkspaceName::forLive())
            ->findChildNodeAggregateByName(
                $sitesNodeIdentifier,
                $site->getNodeName()->toNodeName(),
            );
        if ($siteNodeAggregate instanceof NodeAggregate) {
            // Site node already exists
            return;
        }

        $rootDimensionSpacePoints = $this->interDimensionalVariationGraph->getRootGeneralizations();
        $arbitraryRootDimensionSpacePoint = array_shift($rootDimensionSpacePoints);

        $siteNodeAggregateId = NodeAggregateId::create();
        $this->contentRepository->handle(CreateNodeAggregateWithNode::create(
            WorkspaceName::forLive(),
            $siteNodeAggregateId,
            NodeTypeName::fromString($nodeTypeName),
            OriginDimensionSpacePoint::fromDimensionSpacePoint($arbitraryRootDimensionSpacePoint),
            $sitesNodeIdentifier,
            null,
            PropertyValuesToWrite::fromArray([
                'title' => $site->getName()
            ])
        )->withNodeName($site->getNodeName()->toNodeName()));

        // Handle remaining root dimension space points by creating peer variants
        foreach ($rootDimensionSpacePoints as $rootDimensionSpacePoint) {
            $this->contentRepository->handle(CreateNodeVariant::create(
                WorkspaceName::forLive(),
                $siteNodeAggregateId,
                OriginDimensionSpacePoint::fromDimensionSpacePoint($arbitraryRootDimensionSpacePoint),
                OriginDimensionSpacePoint::fromDimensionSpacePoint($rootDimensionSpacePoint),
            ));
        }
    }

    /**
     * Retrieve the root Node Aggregate ID for the specified $workspace
     * If no root node of the specified $rootNodeTypeName exist, it will be created
     */
    private function getOrCreateRootNodeAggregate(): NodeAggregateId
    {
        $rootNodeTypeName = NodeTypeNameFactory::forSites();
        $rootNodeAggregate = $this->contentRepository->getContentGraph(WorkspaceName::forLive())->findRootNodeAggregateByType(
            $rootNodeTypeName
        );
        if ($rootNodeAggregate !== null) {
            return $rootNodeAggregate->nodeAggregateId;
        }
        $rootNodeAggregateId = NodeAggregateId::create();
        $this->contentRepository->handle(CreateRootNodeAggregateWithNode::create(
            WorkspaceName::forLive(),
            $rootNodeAggregateId,
            $rootNodeTypeName,
        ));
        return $rootNodeAggregateId;
    }
}
