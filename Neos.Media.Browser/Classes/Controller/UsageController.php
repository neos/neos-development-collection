<?php

declare(strict_types=1);

namespace Neos\Media\Browser\Controller;

/*
 * This file is part of the Neos.Media.Browser package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Service\AssetService;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\Domain\Service\WorkspaceService;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Neos\Neos\Service\UserService;
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
     * @var UserService
     */
    protected $userService;

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
     * @var WorkspaceService
     */
    protected $workspaceService;

    /**
     * Get Related Nodes for an asset
     *
     * @param AssetInterface $asset
     * @return void
     */
    public function relatedNodesAction(AssetInterface $asset)
    {
        $currentContentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())->contentRepositoryId;
        $currentContentRepository = $this->contentRepositoryRegistry->get($currentContentRepositoryId);
        $currentUser = $this->userService->getBackendUser();
        assert($currentUser !== null);
        $userWorkspace = $this->workspaceService->getPersonalWorkspaceForUser($currentContentRepositoryId, $currentUser->getId());

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

            $nodeAggregate = $contentRepository->getContentGraph($usage->getWorkspaceName())->findNodeAggregateById(
                $usage->getNodeAggregateId()
            );
            $nodeType = $nodeAggregate ? $contentRepository->getNodeTypeManager()->getNodeType($nodeAggregate->nodeTypeName) : null;

            $workspacePermissions = $this->workspaceService->getWorkspacePermissionsForUser(
                $currentContentRepositoryId,
                $usage->getWorkspaceName(),
                $currentUser
            );

            $workspace = $contentRepository->findWorkspaceByName($usage->getWorkspaceName());

            $inaccessibleRelation['nodeIdentifier'] = $usage->getNodeAggregateId()->value;
            $inaccessibleRelation['workspaceName'] = $usage->getWorkspaceName()->value;
            $inaccessibleRelation['workspace'] = $workspace;
            $inaccessibleRelation['nodeType'] = $nodeType;
            $inaccessibleRelation['accessible'] = $workspacePermissions->read;

            if (!$workspacePermissions->read) {
                $inaccessibleRelations[] = $inaccessibleRelation;
                continue;
            }

            $subgraph = $contentRepository->getContentGraph($usage->getWorkspaceName())->getSubgraph(
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
            if (!$siteNode || !$siteNode->name) {
                $inaccessibleRelations[] = $inaccessibleRelation;
                continue;
            }
            $site = null;
            foreach ($existingSites as $existingSite) {
                if ($siteNode->name->equals($existingSite->getNodeName()->toNodeName())) {
                    $site = $existingSite;
                }
            }
            // guessed it? this should actually never as well ^^
            if (!$site) {
                $inaccessibleRelations[] = $inaccessibleRelation;
                continue;
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
            'userWorkspace' => $userWorkspace
        ]);
    }
}
