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

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindClosestNodeFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Mvc\Controller\ActionController;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Flow\Security\Context;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Service\AssetService;
use Neos\Neos\AssetUsage\Dto\AssetUsageReference;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Model\UserId;
use Neos\Neos\Domain\Model\WorkspaceClassification;
use Neos\Neos\Domain\Model\WorkspaceRole;
use Neos\Neos\Domain\Model\WorkspaceRoleSubjectType;
use Neos\Neos\Domain\Model\WorkspaceTitle;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\Domain\Service\UserService as DomainUserService;
use Neos\Neos\Domain\Service\WorkspaceService;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Neos\Neos\Security\Authorization\ContentRepositoryAuthorizationService;
use Neos\Neos\Service\UserService;

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
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var ContentRepositoryAuthorizationService
     */
    protected $contentRepositoryAuthorizationService;

    /**
     * @Flow\Inject
     * @var PrivilegeManagerInterface
     */
    protected $privilegeManager;

    /**
     * @Flow\Inject
     * @var DomainUserService
     */
    protected $domainUserService;

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

            $nodeAggregate =  $this->securityContext->withoutAuthorizationChecks(
                function () use ($contentRepository, $usage) {
                    try {
                        return $contentRepository->getContentGraph($usage->getWorkspaceName())->findNodeAggregateById(
                            $usage->getNodeAggregateId()
                        );
                    } catch (WorkspaceDoesNotExist $e) {
                        return null;
                    }
                }
            );
            $nodeType = $nodeAggregate ? $contentRepository->getNodeTypeManager()->getNodeType($nodeAggregate->nodeTypeName) : null;

            $workspacePermissions = $this->contentRepositoryAuthorizationService->getWorkspacePermissions($currentContentRepositoryId, $usage->getWorkspaceName(), $this->securityContext->getRoles(), $this->userService->getBackendUser()?->getId());
            $workspace = $contentRepository->findWorkspaceByName($usage->getWorkspaceName());

            $inaccessibleRelation['nodeIdentifier'] = $usage->getNodeAggregateId()->value;
            $inaccessibleRelation['workspace'] = $workspace;
            $inaccessibleRelation['relevantWorkspaceMetadata'] = $this->getRelevantMetadataFromInaccessibleWorkspace($workspace, $contentRepository);
            $inaccessibleRelation['nodeType'] = $nodeType;
            $inaccessibleRelation['accessible'] = $workspacePermissions->read;

            // the workspace from `usage` might not be found, but we expect a given workspace in further function
            // and user should have access to it, if not we have an inaccessible relation
            if ($workspace === null || !$workspacePermissions->read) {
                $inaccessibleRelations[] = $inaccessibleRelation;
                continue;
            }

            $subgraph = $contentRepository->getContentGraph($usage->getWorkspaceName())->getSubgraph(
                $usage->getOriginDimensionSpacePoint()->toDimensionSpacePoint(),
                VisibilityConstraints::createEmpty()
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
                'workspaceMetadata' => $this->workspaceService->getWorkspaceMetadata($contentRepository->id, $workspace->workspaceName),
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
            'userWorkspace' => $userWorkspace,
        ]);
    }

    /**
     * @return array{title: WorkspaceTitle|null, relatedUserName: string|null, personalWorkspace: bool, privateWorkspace: bool}
     */
    private function getRelevantMetadataFromInaccessibleWorkspace(?Workspace $workspace, ?ContentRepository $contentRepository): array
    {
        $structuredReturn = [
            'title' => null,
            'relatedUserName' => '',
            'personalWorkspace' => false,
            'privateWorkspace' => false,
        ];

        if ($workspace === null) {
            return $structuredReturn;
        }

        $currentAccount = $this->securityContext->getAccount();

        if ($currentAccount != null && $contentRepository != null && $this->privilegeManager->isPrivilegeTargetGranted('Neos.Media.Browser:WorkspaceName')) {
            $workspaceMetadata = $this->workspaceService->getWorkspaceMetadata($contentRepository->id, $workspace->workspaceName);
            $workspaceOwner = $workspaceMetadata->ownerUserId
                ? $this->domainUserService->findUserById($workspaceMetadata->ownerUserId)
                : null;

            $roleAssignments = $this->workspaceService->getWorkspaceRoleAssignments(
                $contentRepository->id,
                $workspace->workspaceName
            );
            $relatedUser = null;
            foreach ($roleAssignments as $roleAssignment) {
                if (($roleAssignment->role->value !== WorkspaceRole::VIEWER->value) && ($roleAssignment->subject->type->value === WorkspaceRoleSubjectType::USER->value)) {
                    $relatedUser = $this->domainUserService->findUserById(UserId::fromString($roleAssignment->subject->value));
                    break;
                }
            }

            if ($workspaceMetadata->classification->value === WorkspaceClassification::PERSONAL->value) {
                $structuredReturn['relatedUserName'] = $workspaceOwner?->getLabel();
                $structuredReturn['personalWorkspace'] = true;
            } else {
                $structuredReturn['title'] = $workspaceMetadata->title;
                $structuredReturn['relatedUserName'] = $relatedUser?->getLabel();
                $structuredReturn['privateWorkspace'] = true;
            }
        }

        return $structuredReturn;
    }
}
