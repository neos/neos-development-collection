<?php

/*
 * This file is part of the Neos.Media.Browser package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Media\Browser\Controller;

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFoundException;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFound;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Service\AssetService;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\Domain\Workspace\WorkspaceProvider;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Neos\Neos\Domain\Service\UserService as DomainUserService;
use Neos\Neos\AssetUsage\Dto\AssetUsageReference;
use Neos\Neos\Domain\Model\Site;

/**
 * Controller for asset usage handling
 *
 * @Flow\Scope("singleton")
 */
class UsageController extends ActionController
{
    /**
     * @Flow\Inject
     * @var AssetService
     */
    protected $assetService;

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
     * @var DomainUserService
     */
    protected $domainUserService;

    /**
     * @Flow\Inject
     * @var WorkspaceProvider
     */
    protected $workspaceProvider;

    /**
     * Get Related Nodes for an asset
     *
     * @param AssetInterface $asset
     * @return void
     */
    public function relatedNodesAction(AssetInterface $asset): void
    {
        $currentContentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())->contentRepositoryId;
        $personalWorkspace = $this->workspaceProvider->providePrimaryPersonalWorkspaceForCurrentUser($currentContentRepositoryId);
        $currentContentRepository = $this->contentRepositoryRegistry->get($currentContentRepositoryId);

        $usageReferences = $this->assetService->getUsageReferences($asset);
        $relatedNodes = [];
        $inaccessibleRelations = [];

        $existingSites = $this->siteRepository->findAll();

        foreach ($usageReferences as $usage) {
            $inaccessibleRelation = [
                'type' => get_class($usage),
                'usage' => $usage
            ];

            if (!$usage instanceof AssetUsageReference) {
                $inaccessibleRelations[] = $inaccessibleRelation;
                continue;
            }

            $contentRepository = $this->contentRepositoryRegistry->get($usage->getContentRepositoryId());

            $workspace = $contentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamId($usage->getContentStreamId());

            // FIXME: AssetUsageReference->workspaceName ?
            $nodeAggregate = $contentRepository->getContentGraph($workspace->workspaceName)->findNodeAggregateById(
                $usage->getNodeAggregateId()
            );
            try {
                $nodeType = $contentRepository->getNodeTypeManager()->getNodeType($nodeAggregate->nodeTypeName);
            } catch (NodeTypeNotFound $e) {
                $nodeType = null;
            }

            $accessible = $this->domainUserService->currentUserCanReadWorkspace($workspace);

            $inaccessibleRelation['nodeIdentifier'] = $usage->getNodeAggregateId()->value;
            $inaccessibleRelation['workspaceName'] = $workspace->workspaceName->value;
            $inaccessibleRelation['workspace'] = $workspace;
            $inaccessibleRelation['nodeType'] = $nodeType;
            $inaccessibleRelation['accessible'] = $accessible;

            if (!$accessible) {
                $inaccessibleRelations[] = $inaccessibleRelation;
                continue;
            }

            $subgraph = $contentRepository->getContentGraph($workspace->workspaceName)->getSubgraph(
                $usage->getOriginDimensionSpacePoint()->toDimensionSpacePoint(),
                VisibilityConstraints::withoutRestrictions()
            );

            $node = $subgraph->findNodeById($usage->getNodeAggregateId());
            // this should actually never happen.
            if (!$node) {
                $inaccessibleRelations[] = $inaccessibleRelation;
                continue;
            }

            $documentNode = $subgraph->findClosestNode($node->aggregateId, FindClosestNodeFilter::create(nodeTypes: NodeTypeNameFactory::NAME_DOCUMENT));
            // this should actually never happen, too.
            if (!$documentNode) {
                $inaccessibleRelations[] = $inaccessibleRelation;
                continue;
            }

            $siteNode = $subgraph->findClosestNode($node->aggregateId, FindClosestNodeFilter::create(nodeTypes: NodeTypeNameFactory::NAME_SITE));
            // this should actually never happen, too. :D
            if (!$siteNode) {
                $inaccessibleRelations[] = $inaccessibleRelation;
                continue;
            }
            foreach ($existingSites as $existingSite) {
                /** @var Site $existingSite * */
                if ($siteNode->name->equals($existingSite->getNodeName()->toNodeName())) {
                    $site = $existingSite;
                }
            }

            $relatedNodes[$site->getNodeName()->value]['site'] = $site;
            $relatedNodes[$site->getNodeName()->value]['nodes'][] = [
                'node' => $node,
                'workspace' => $workspace,
                'documentNode' => $documentNode
            ];
        }

        $this->view->assignMultiple([
            'totalUsageCount' => count($usageReferences),
            'nodeUsageClass' => AssetUsageReference::class,
            'asset' => $asset,
            'inaccessibleRelations' => $inaccessibleRelations,
            'relatedNodes' => $relatedNodes,
            'contentDimensions' => $currentContentRepository->getContentDimensionSource()->getContentDimensionsOrderedByPriority(),
            'userWorkspaceName' => $personalWorkspace->name->value
        ]);
    }
}
