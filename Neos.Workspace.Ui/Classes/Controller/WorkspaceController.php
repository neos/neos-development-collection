<?php

/*
 * This file is part of the Neos.Workspace.Ui package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Workspace\Ui\Controller;

use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Exception\WorkspaceAlreadyExists;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\DeleteWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdsToPublishOrDiscard;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindAncestorNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAddress;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Diff\Diff;
use Neos\Diff\Renderer\Html\HtmlArrayRenderer;
use Neos\Error\Messages\Message;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\I18n\Exception\IndexOutOfBoundsException;
use Neos\Flow\I18n\Exception\InvalidFormatPlaceholderException;
use Neos\Flow\I18n\Translator;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Security\Context;
use Neos\Fusion\View\FusionView;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\Domain\Model\SiteNodeName;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Model\UserId;
use Neos\Neos\Domain\Model\WorkspaceClassification;
use Neos\Neos\Domain\Model\WorkspaceDescription;
use Neos\Neos\Domain\Model\WorkspaceRole;
use Neos\Neos\Domain\Model\WorkspaceRoleAssignment;
use Neos\Neos\Domain\Model\WorkspaceTitle;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\Domain\Service\WorkspacePublishingService;
use Neos\Neos\Domain\Service\WorkspaceService;
use Neos\Neos\FrontendRouting\NodeUriBuilderFactory;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
use Neos\Neos\PendingChangesProjection\ChangeFinder;
use Neos\Neos\Utility\NodeTypeWithFallbackProvider;
use Neos\Workspace\Ui\Model\WorkspaceDetails;
use Neos\Workspace\Ui\Model\WorkspaceDetailsCollection;
use Neos\Workspace\Ui\ViewModel\PendingChanges;

/**
 * The Neos Workspace module controller
 */
#[Flow\Scope('singleton')]
class WorkspaceController extends AbstractModuleController
{
    use NodeTypeWithFallbackProvider;

    protected $defaultViewObjectName = FusionView::class;

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected NodeUriBuilderFactory $nodeUriBuilderFactory;

    #[Flow\Inject]
    protected SiteRepository $siteRepository;

    #[Flow\Inject]
    protected PropertyMapper $propertyMapper;

    #[Flow\Inject]
    protected Context $securityContext;

    #[Flow\Inject]
    protected UserService $userService;

    #[Flow\Inject]
    protected PackageManager $packageManager;

    #[Flow\Inject]
    protected WorkspacePublishingService $workspacePublishingService;

    #[Flow\Inject]
    protected WorkspaceService $workspaceService;

    #[Flow\Inject]
    protected Translator $translator;

    /**
     * Display a list of unpublished content
     */
    public function indexAction(): void
    {
        $currentUser = $this->userService->getCurrentUser();
        if ($currentUser === null) {
            throw new \RuntimeException('No user authenticated', 1718308216);
        }

        $contentRepositoryIds = $this->contentRepositoryRegistry->getContentRepositoryIds();
        $numberOfContentRepositories = $contentRepositoryIds->count();
        if ($numberOfContentRepositories === 0) {
            throw new \RuntimeException('No content repository configured', 1718296290);
        }
        if ($this->request->hasArgument('contentRepositoryId')) {
            $contentRepositoryIdArgument = $this->request->getArgument('contentRepositoryId');
            assert(is_string($contentRepositoryIdArgument));
            $contentRepositoryId = ContentRepositoryId::fromString($contentRepositoryIdArgument);
        } else {
            $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())->contentRepositoryId;
        }
        $this->view->assign('contentRepositoryIds', $contentRepositoryIds);
        $this->view->assign('contentRepositoryId', $contentRepositoryId->value);
        $this->view->assign('displayContentRepositorySelector', $numberOfContentRepositories > 1);

        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $userWorkspace = $this->getUserWorkspace($contentRepository);
        $workspacesAndCounts = $this->getWorkspacesAndChangeCounts($userWorkspace, $contentRepository);

        $this->view->assignMultiple([
            'userWorkspace' => $userWorkspace,
            'workspacesAndChangeCounts' => $workspacesAndCounts,
            'flashMessages' => $this->controllerContext->getFlashMessageContainer()->getMessagesAndFlush(),
        ]);
    }

    public function showAction(WorkspaceName $workspace): void
    {
        $currentUser = $this->userService->getCurrentUser();
        if ($currentUser === null) {
            throw new \RuntimeException('No user authenticated', 1720371024);
        }
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $workspaceObj = $contentRepository->findWorkspaceByName($workspace);
        if (is_null($workspaceObj)) {
            /** @todo add flash message */
            $this->redirect('index');
        }
        $workspaceMetadata = $this->workspaceService->getWorkspaceMetadata($contentRepositoryId, $workspace);
        $baseWorkspaceMetadata = null;
        $baseWorkspacePermissions = null;
        if ($workspaceObj->baseWorkspaceName !== null) {
            $baseWorkspace = $contentRepository->findWorkspaceByName($workspaceObj->baseWorkspaceName);
            assert($baseWorkspace !== null);
            $baseWorkspaceMetadata = $this->workspaceService->getWorkspaceMetadata($contentRepositoryId, $baseWorkspace->workspaceName);
            $baseWorkspacePermissions = $this->workspaceService->getWorkspacePermissionsForUser($contentRepositoryId, $baseWorkspace->workspaceName, $currentUser);
        }
        $this->view->assignMultiple([
            'selectedWorkspace' => $workspaceObj,
            'selectedWorkspaceLabel' => $workspaceMetadata->title->value,
            'baseWorkspaceName' => $workspaceObj->baseWorkspaceName,
            'baseWorkspaceLabel' => $baseWorkspaceMetadata?->title->value,
            'canPublishToBaseWorkspace' => $baseWorkspacePermissions?->write ?? false,
            'siteChanges' => $this->computeSiteChanges($workspaceObj, $contentRepository),
            'contentDimensions' => $contentRepository->getContentDimensionSource()->getContentDimensionsOrderedByPriority()
        ]);
    }

    public function newAction(ContentRepositoryId $contentRepositoryId): void
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $this->view->assign('baseWorkspaceOptions', $this->prepareBaseWorkspaceOptions($contentRepository));
        $this->view->assign('contentRepositoryId', $contentRepositoryId->value);
    }

    public function createAction(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceTitle $title,
        WorkspaceName $baseWorkspace,
        WorkspaceDescription $description,
    ): void {
        $currentUser = $this->userService->getCurrentUser();
        if ($currentUser === null) {
            throw new \RuntimeException('No user authenticated', 1718303756);
        }
        $workspaceName = $this->workspaceService->getUniqueWorkspaceName($contentRepositoryId, $title->value);
        try {
            $this->workspaceService->createSharedWorkspace(
                $contentRepositoryId,
                $workspaceName,
                $title,
                $description,
                $baseWorkspace,
            );
        } catch (WorkspaceAlreadyExists $exception) {
            $this->addFlashMessage(
                $this->getModuleLabel('workspaces.workspaceWithThisTitleAlreadyExists'),
                '',
                Message::SEVERITY_WARNING
            );
            $this->throwStatus(400, 'Workspace with this title already exists');
        } catch (\Exception $exception) {
            $this->addFlashMessage(
                $exception->getMessage(),
                $this->getModuleLabel('workspaces.workspaceCouldNotBeCreated'),
                Message::SEVERITY_ERROR
            );
            $this->throwStatus(500, 'Workspace could not be created');
        }
        $this->workspaceService->assignWorkspaceRole(
            $contentRepositoryId,
            $workspaceName,
            WorkspaceRoleAssignment::createForUser(
                $currentUser->getId(),
                WorkspaceRole::MANAGER,
            )
        );
        $this->workspaceService->assignWorkspaceRole(
            $contentRepositoryId,
            $workspaceName,
            WorkspaceRoleAssignment::createForGroup(
                'Neos.Neos:AbstractEditor',
                WorkspaceRole::COLLABORATOR,
            )
        );
        $this->addFlashMessage($this->getModuleLabel('workspaces.workspaceHasBeenCreated', [$title->value]));
        $this->redirect('index');
    }

    /**
     * Edit a workspace
     */
    public function editAction(WorkspaceName $workspaceName): void
    {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $workspace = $contentRepository->findWorkspaceByName($workspaceName);
        if (is_null($workspace)) {
            $this->addFlashMessage(
                // todo add $workspaceName to label
                $this->getModuleLabel('workspaces.workspaceDoesNotExist'),
                '',
                Message::SEVERITY_ERROR
            );
            $this->throwStatus(404, 'Workspace does not exist');
        }

        $this->view->assign('workspace', $workspace);
        $this->view->assign('baseWorkspaceOptions', $this->prepareBaseWorkspaceOptions($contentRepository, $workspaceName));
        // TODO: $this->view->assign('disableBaseWorkspaceSelector',
        // $this->publishingService->getUnpublishedNodesCount($workspace) > 0);

        // TODO fix $this->userService->currentUserCanTransferOwnershipOfWorkspace($workspace)
        $this->view->assign('showOwnerSelector', false);

        $this->view->assign('ownerOptions', $this->prepareOwnerOptions());
    }

    /**
     * Update a workspace
     *
     * @Flow\Validate(argumentName="title", type="\Neos\Flow\Validation\Validator\NotEmptyValidator")
     * @param WorkspaceName $workspaceName
     * @param WorkspaceTitle $title Human friendly title of the workspace, for example "Christmas Campaign"
     * @param WorkspaceDescription $description A description explaining the purpose of the new workspace
     * @return void
     */
    public function updateAction(
        WorkspaceName $workspaceName,
        WorkspaceTitle $title,
        WorkspaceDescription $description,
    ): void {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        if ($title->value === '') {
            $title = WorkspaceTitle::fromString($workspaceName->value);
        }

        $workspace = $contentRepository->findWorkspaceByName($workspaceName);
        if ($workspace === null) {
            $this->addFlashMessage(
                $this->getModuleLabel('workspaces.workspaceDoesNotExist'),
                '',
                Message::SEVERITY_ERROR
            );
            $this->throwStatus(404, 'Workspace does not exist');
        }
        $this->workspaceService->setWorkspaceTitle(
            $contentRepositoryId,
            $workspaceName,
            $title,
        );
        $this->workspaceService->setWorkspaceDescription(
            $contentRepositoryId,
            $workspaceName,
            $description,
        );
        $this->addFlashMessage(
            $this->getModuleLabel(
                'workspaces.workspaceHasBeenUpdated',
                [$title->value],
            )
        );

        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $userWorkspace = $this->getUserWorkspace($contentRepository);
        $workspacesAndCounts = $this->getWorkspacesAndChangeCounts($userWorkspace, $contentRepository);

        $this->view->assignMultiple([
            'userWorkspace' => $userWorkspace,
            'workspacesAndChangeCounts' => $workspacesAndCounts,
        ]);
    }

    /**
     * Delete a workspace
     *
     * TODO: Add force delete option to ignore unpublished nodes or dependent workspaces, the later should be rebased instead
     *
     * @param WorkspaceName $workspaceName A workspace to delete
     * @throws IndexOutOfBoundsException
     * @throws InvalidFormatPlaceholderException
     * @throws StopActionException
     * @throws DBALException
     */
    public function deleteAction(WorkspaceName $workspaceName): void
    {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $workspace = $contentRepository->findWorkspaceByName($workspaceName);
        if ($workspace === null) {
            $this->addFlashMessage(
                $this->getModuleLabel('workspaces.workspaceDoesNotExist'),
                '',
                Message::SEVERITY_ERROR
            );
            $this->throwStatus(404, 'Workspace does not exist');
        }

        $workspaceMetadata = $this->workspaceService->getWorkspaceMetadata($contentRepositoryId, $workspace->workspaceName);

        if ($workspaceMetadata->classification === WorkspaceClassification::PERSONAL) {
            $this->throwStatus(403, 'Personal workspaces cannot be deleted');
        }

        $dependentWorkspaces = $contentRepository->findWorkspaces()->getDependantWorkspaces($workspaceName);
        if (!$dependentWorkspaces->isEmpty()) {
            $dependentWorkspaceTitles = [];
            /** @var Workspace $dependentWorkspace */
            foreach ($dependentWorkspaces as $dependentWorkspace) {
                $dependentWorkspaceMetadata = $this->workspaceService->getWorkspaceMetadata($contentRepositoryId, $dependentWorkspace->workspaceName);
                $dependentWorkspaceTitles[] = $dependentWorkspaceMetadata->title->value;
            }

            $message = $this->getModuleLabel(
                'workspaces.workspaceCannotBeDeletedBecauseOfDependencies',
                [$workspaceMetadata->title->value, implode(', ', $dependentWorkspaceTitles)],
            );
            $this->addFlashMessage($message, '', Message::SEVERITY_WARNING);
            $this->throwStatus(403, 'Workspace has dependencies');
        }

        $nodesCount = 0;

        try {
            $nodesCount = $contentRepository->projectionState(ChangeFinder::class)
                ->countByContentStreamId(
                    $workspace->currentContentStreamId
                );
        } catch (\Exception $exception) {
            $message = $this->getModuleLabel(
                'workspaces.notDeletedErrorWhileFetchingUnpublishedNodes',
                [$workspaceMetadata->title->value],
            );
            $this->addFlashMessage($message, '', Message::SEVERITY_WARNING);
            $this->throwStatus(500, 'Error while fetching unpublished nodes');
        }
        if ($nodesCount > 0) {
            $message = $this->getModuleLabel(
                'workspaces.workspaceCannotBeDeletedBecauseOfUnpublishedNodes',
                [$workspaceMetadata->title->value, $nodesCount],
                $nodesCount,
            );
            $this->addFlashMessage($message, '', Message::SEVERITY_WARNING);
            $this->throwStatus(403, 'Workspace has unpublished nodes');
        }

        // Render a confirmation form if the request is not a POST request
        if ($this->request->getHttpRequest()->getMethod() === 'POST') {
            $contentRepository->handle(
                DeleteWorkspace::create(
                    $workspaceName,
                )
            );

            $this->addFlashMessage(
                $this->getModuleLabel(
                    'workspaces.workspaceHasBeenRemoved',
                    [$workspaceMetadata->title->value],
                )
            );
        } else {
            $this->view->assign('workspace', $workspace);
        }
    }

    /**
     * Rebase the current users personal workspace onto the given $targetWorkspace and then
     * redirects to the $targetNode in the content module.
     */
    public function rebaseAndRedirectAction(string $targetNode, Workspace $targetWorkspace): void
    {
        $targetNodeAddress = NodeAddress::fromJsonString(
            $targetNode
        );

        $user = $this->userService->getCurrentUser();
        if ($user === null) {
            throw new \RuntimeException('No account is authenticated', 1710068880);
        }
        $personalWorkspace = $this->workspaceService->getPersonalWorkspaceForUser($targetNodeAddress->contentRepositoryId, $user->getId());

        /** @todo do something else
         * if ($personalWorkspace !== $targetWorkspace) {
         * if ($this->publishingService->getUnpublishedNodesCount($personalWorkspace) > 0) {
         * $message = $this->getModuleLabel(
         * 'workspaces.cantEditBecauseWorkspaceContainsChanges',
         * );
         * $this->addFlashMessage($message, '', Message::SEVERITY_WARNING, [], 1437833387);
         * $this->redirect('show', null, null, ['workspace' => $targetWorkspace]);
         * }
         * $personalWorkspace->setBaseWorkspace($targetWorkspace);
         * $this->workspaceFinder->update($personalWorkspace);
         * }
         */

        $targetNodeAddressInPersonalWorkspace = NodeAddress::create(
            $targetNodeAddress->contentRepositoryId,
            $personalWorkspace->workspaceName,
            $targetNodeAddress->dimensionSpacePoint,
            $targetNodeAddress->aggregateId
        );

        if ($this->packageManager->isPackageAvailable('Neos.Neos.Ui')) {
            $mainRequest = $this->controllerContext->getRequest()->getMainRequest();
            $this->uriBuilder->setRequest($mainRequest);

            $this->redirect(
                'index',
                'Backend',
                'Neos.Neos.Ui',
                ['node' => $targetNodeAddressInPersonalWorkspace->toJson()]
            );
        }

        $this->redirectToUri(
            $this->nodeUriBuilderFactory->forActionRequest($this->request)
                ->uriFor($targetNodeAddressInPersonalWorkspace)
        );
    }

    /**
     * Publish a single node
     *
     * @param string $nodeAddress
     * @param WorkspaceName $selectedWorkspace
     */
    public function publishNodeAction(string $nodeAddress, WorkspaceName $selectedWorkspace): void
    {
        $nodeAddress = NodeAddress::fromJsonString($nodeAddress);

        $contentRepository = $this->contentRepositoryRegistry->get($nodeAddress->contentRepositoryId);

        $command = PublishIndividualNodesFromWorkspace::create(
            $selectedWorkspace,
            NodeIdsToPublishOrDiscard::create(
                new NodeIdToPublishOrDiscard(
                    $nodeAddress->aggregateId,
                    $nodeAddress->dimensionSpacePoint
                )
            ),
        );
        $contentRepository->handle($command);

        $this->addFlashMessage($this->getModuleLabel('workspaces.selectedChangeHasBeenPublished'));
        $this->redirect('show', null, null, ['workspace' => $selectedWorkspace->value]);
    }

    /**
     * Discard a a single node
     *
     * @param string $nodeAddress
     * @param WorkspaceName $selectedWorkspace
     */
    public function discardNodeAction(string $nodeAddress, WorkspaceName $selectedWorkspace): void
    {
        $nodeAddress = NodeAddress::fromJsonString($nodeAddress);

        $contentRepository = $this->contentRepositoryRegistry->get($nodeAddress->contentRepositoryId);

        $command = DiscardIndividualNodesFromWorkspace::create(
            $selectedWorkspace,
            NodeIdsToPublishOrDiscard::create(
                new NodeIdToPublishOrDiscard(
                    $nodeAddress->aggregateId,
                    $nodeAddress->dimensionSpacePoint
                )
            ),
        );
        $contentRepository->handle($command);

        $this->addFlashMessage($this->getModuleLabel('workspaces.selectedChangeHasBeenDiscarded'));
        $this->redirect('show', null, null, ['workspace' => $selectedWorkspace->value]);
    }

    /**
     * @psalm-param list<string> $nodes
     * @throws IndexOutOfBoundsException
     * @throws InvalidFormatPlaceholderException
     * @throws StopActionException
     */
    public function publishOrDiscardNodesAction(array $nodes, string $action, string $selectedWorkspace): void
    {
        $selectedWorkspaceName = WorkspaceName::fromString($selectedWorkspace);
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $nodesToPublishOrDiscard = [];
        foreach ($nodes as $node) {
            $nodeAddress = NodeAddress::fromJsonString($node);
            $nodesToPublishOrDiscard[] = new NodeIdToPublishOrDiscard(
                $nodeAddress->aggregateId,
                $nodeAddress->dimensionSpacePoint
            );
        }

        switch ($action) {
            case 'publish':
                $command = PublishIndividualNodesFromWorkspace::create(
                    $selectedWorkspaceName,
                    NodeIdsToPublishOrDiscard::create(...$nodesToPublishOrDiscard),
                );
                $contentRepository->handle($command);
                $this->addFlashMessage(
                    $this->getModuleLabel('workspaces.selectedChangesHaveBeenPublished')
                );
                break;
            case 'discard':
                $command = DiscardIndividualNodesFromWorkspace::create(
                    $selectedWorkspaceName,
                    NodeIdsToPublishOrDiscard::create(...$nodesToPublishOrDiscard),
                );
                $contentRepository->handle($command);
                $this->addFlashMessage($this->getModuleLabel('workspaces.selectedChangesHaveBeenDiscarded'));
                break;
            default:
                throw new \RuntimeException('Invalid action "' . htmlspecialchars($action) . '" given.', 1346167441);
        }

        $this->redirect('show', null, null, ['workspace' => $selectedWorkspaceName->value]);
    }

    /**
     * Publishes the whole workspace
     */
    public function publishWorkspaceAction(WorkspaceName $workspace): void
    {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())->contentRepositoryId;
        $publishingResult = $this->workspacePublishingService->publishWorkspace(
            $contentRepositoryId,
            $workspace,
        );
        $this->addFlashMessage(
            $this->getModuleLabel(
                'workspaces.allChangesInWorkspaceHaveBeenPublished',
                [
                    htmlspecialchars($workspace->value),
                    htmlspecialchars($publishingResult->targetWorkspaceName->value)
                ],
            )
        );
        $this->redirect('index');
    }

    /**
     * Discards content of the whole workspace
     *
     * TODO: Adjust param to workspaceName
     * @param WorkspaceName $workspace
     */
    public function discardWorkspaceAction(WorkspaceName $workspace): void
    {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())->contentRepositoryId;
        $this->workspacePublishingService->discardAllWorkspaceChanges(
            $contentRepositoryId,
            $workspace,
        );
        $this->addFlashMessage(
            $this->getModuleLabel(
                'workspaces.allChangesInWorkspaceHaveBeenDiscarded',
                [htmlspecialchars($workspace->value)],
            )
        );
        $this->redirect('index');
    }

    /**
     * Computes the number of added, changed and removed nodes for the given workspace
     */
    protected function computePendingChanges(Workspace $selectedWorkspace, ContentRepository $contentRepository): PendingChanges
    {
        $changesCount = ['new' => 0, 'changed' => 0, 'removed' => 0];
        foreach ($this->computeSiteChanges($selectedWorkspace, $contentRepository) as $siteChanges) {
            foreach ($siteChanges['documents'] as $documentChanges) {
                foreach ($documentChanges['changes'] as $change) {
                    if ($change['isRemoved'] === true) {
                        $changesCount['removed']++;
                    } elseif ($change['isNew']) {
                        $changesCount['new']++;
                    } else {
                        $changesCount['changed']++;
                    }
                }
            }
        }
        return new PendingChanges(new: $changesCount['new'], changed: $changesCount['changed'], removed: $changesCount['removed']);
    }

    /**
     * Builds an array of changes for sites in the given workspace
     * @return array<string,mixed>
     */
    protected function computeSiteChanges(Workspace $selectedWorkspace, ContentRepository $contentRepository): array
    {
        $siteChanges = [];
        $changes = $contentRepository->projectionState(ChangeFinder::class)
            ->findByContentStreamId(
                $selectedWorkspace->currentContentStreamId
            );

        foreach ($changes as $change) {
            $workspaceName = $selectedWorkspace->workspaceName;
            if ($change->deleted) {
                // If we deleted a node, there is no way for us to anymore find the deleted node in the ContentStream
                // where the node was deleted.
                // Thus, to figure out the rootline for display, we check the *base workspace* Content Stream.
                //
                // This is safe because the UI basically shows what would be removed once the deletion is published.
                $baseWorkspace = $this->getBaseWorkspaceWhenSureItExists($selectedWorkspace, $contentRepository);
                $workspaceName = $baseWorkspace->workspaceName;
            }
            $subgraph = $contentRepository->getContentGraph($workspaceName)->getSubgraph(
                $change->originDimensionSpacePoint->toDimensionSpacePoint(),
                VisibilityConstraints::withoutRestrictions()
            );

            $node = $subgraph->findNodeById($change->nodeAggregateId);
            if ($node) {
                $documentNode = null;
                $siteNode = null;
                $ancestors = $subgraph->findAncestorNodes(
                    $node->aggregateId,
                    FindAncestorNodesFilter::create()
                );
                $ancestors = Nodes::fromArray([$node])->merge($ancestors);

                $nodePathSegments = [];
                $documentPathSegments = [];
                foreach ($ancestors as $ancestor) {
                    $pathSegment = $ancestor->name ?: NodeName::fromString($ancestor->aggregateId->value);
                    // Don't include `sites` path as they are not needed
                    // by the HTML/JS magic and won't be included as `$documentPathSegments`
                    if (!$this->getNodeType($ancestor)->isOfType(NodeTypeNameFactory::NAME_SITES)) {
                        $nodePathSegments[] = $pathSegment;
                    }
                    if ($this->getNodeType($ancestor)->isOfType(NodeTypeNameFactory::NAME_DOCUMENT)) {
                        $documentPathSegments[] = $pathSegment;
                        if (is_null($documentNode)) {
                            $documentNode = $ancestor;
                        }
                    }
                    if ($this->getNodeType($ancestor)->isOfType(NodeTypeNameFactory::NAME_SITE)) {
                        $siteNode = $ancestor;
                    }
                }

                // Neither $documentNode, $siteNode or its cannot really be null, this is just for type checks;
                // We should probably throw an exception though
                if ($documentNode !== null && $siteNode !== null && $siteNode->name) {
                    $siteNodeName = $siteNode->name->value;
                    // Reverse `$documentPathSegments` to start with the site node.
                    // The paths are used for grouping the nodes and for selecting a tree of nodes.
                    $documentPath = implode(
                        '/',
                        array_reverse(
                            array_map(
                                fn(NodeName $nodeName): string => $nodeName->value,
                                $documentPathSegments
                            )
                        )
                    );
                    // Reverse `$nodePathSegments` to start with the site node.
                    // The paths are used for grouping the nodes and for selecting a tree of nodes.
                    $relativePath = implode(
                        '/',
                        array_reverse(
                            array_map(
                                fn(NodeName $nodeName): string => $nodeName->value,
                                $nodePathSegments
                            )
                        )
                    );
                    if (!isset($siteChanges[$siteNodeName]['siteNode'])) {
                        $siteChanges[$siteNodeName]['siteNode']
                            = $this->siteRepository->findOneByNodeName(SiteNodeName::fromString($siteNodeName));
                    }

                    $siteChanges[$siteNodeName]['documents'][$documentPath]['documentNode'] = $documentNode;
                    // We need to set `isNew` and `isMoved` on document level to make our JS behave as before.
                    if ($documentNode->equals($node)) {
                        $siteChanges[$siteNodeName]['documents'][$documentPath]['isNew'] = $change->created;
                        $siteChanges[$siteNodeName]['documents'][$documentPath]['isMoved'] = $change->moved;
                    }

                    // As for changes of type `delete` we are using nodes from the live workspace
                    // we can't create a serialized nodeAddress from the node.
                    // Instead, we use the original stored values.
                    $nodeAddress = NodeAddress::create(
                        $contentRepository->id,
                        $selectedWorkspace->workspaceName,
                        $change->originDimensionSpacePoint->toDimensionSpacePoint(),
                        $change->nodeAggregateId
                    );

                    $change = [
                        'node' => $node,
                        'serializedNodeAddress' => $nodeAddress->toJson(),
                        'isRemoved' => $change->deleted,
                        'isNew' => $change->created,
                        'isMoved' => $change->moved,
                        'contentChanges' => $this->renderContentChanges(
                            $node,
                            $change->contentStreamId,
                            $contentRepository
                        )
                    ];
                    $nodeType = $this->getNodeType($node);
                    if ($nodeType->isOfType('Neos.Neos:Node')) {
                        $change['configuration'] = $nodeType->getFullConfiguration();
                    }
                    $siteChanges[$siteNodeName]['documents'][$documentPath]['changes'][$relativePath] = $change;
                }
            }
        }

        ksort($siteChanges);
        foreach ($siteChanges as $siteKey => $site) {
            foreach ($site['documents'] as $documentKey => $document) {
                ksort($siteChanges[$siteKey]['documents'][$documentKey]['changes']);
            }
            ksort($siteChanges[$siteKey]['documents']);
        }
        return $siteChanges;
    }

    /**
     * Retrieves the given node's corresponding node in the base content stream
     * (that is, which would be overwritten if the given node would be published)
     */
    protected function getOriginalNode(
        Node $modifiedNode,
        WorkspaceName $baseWorkspaceName,
        ContentRepository $contentRepository,
    ): ?Node {
        $baseSubgraph = $contentRepository->getContentGraph($baseWorkspaceName)->getSubgraph(
            $modifiedNode->dimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );
        return $baseSubgraph->findNodeById($modifiedNode->aggregateId);
    }

    /**
     * Renders the difference between the original and the changed content of the given node and returns it, along
     * with meta information, in an array.
     *
     * @return array<string,mixed>
     */
    protected function renderContentChanges(
        Node $changedNode,
        ContentStreamId $contentStreamIdOfOriginalNode,
        ContentRepository $contentRepository,
    ): array {
        $currentWorkspace = $contentRepository->findWorkspaces()->find(
            fn (Workspace $potentialWorkspace) => $potentialWorkspace->currentContentStreamId->equals($contentStreamIdOfOriginalNode)
        );
        $originalNode = null;
        if ($currentWorkspace !== null) {
            $baseWorkspace = $this->getBaseWorkspaceWhenSureItExists($currentWorkspace, $contentRepository);
            $originalNode = $this->getOriginalNode($changedNode, $baseWorkspace->workspaceName, $contentRepository);
        }


        $contentChanges = [];

        $changeNodePropertiesDefaults = $this->getNodeType($changedNode)->getDefaultValuesForProperties();

        $renderer = new HtmlArrayRenderer();
        foreach ($changedNode->properties as $propertyName => $changedPropertyValue) {
            if (
                ($originalNode === null && empty($changedPropertyValue))
                || (
                    isset($changeNodePropertiesDefaults[$propertyName])
                    && $changedPropertyValue === $changeNodePropertiesDefaults[$propertyName]
                )
            ) {
                continue;
            }

            $originalPropertyValue = ($originalNode?->getProperty($propertyName));

            if ($changedPropertyValue === $originalPropertyValue) {
                // TODO  && !$changedNode->isRemoved()
                continue;
            }

            if (!is_object($originalPropertyValue) && !is_object($changedPropertyValue)) {
                $originalSlimmedDownContent = $this->renderSlimmedDownContent($originalPropertyValue);
                // TODO $changedSlimmedDownContent = $changedNode->isRemoved()
                // ? ''
                // : $this->renderSlimmedDownContent($changedPropertyValue);
                $changedSlimmedDownContent = $this->renderSlimmedDownContent($changedPropertyValue);

                $diff = new Diff(
                    explode("\n", $originalSlimmedDownContent),
                    explode("\n", $changedSlimmedDownContent),
                    ['context' => 1]
                );
                $diffArray = $diff->render($renderer);
                $this->postProcessDiffArray($diffArray);

                if (count($diffArray) > 0) {
                    $contentChanges[$propertyName] = [
                        'type' => 'text',
                        'propertyLabel' => $this->getPropertyLabel($propertyName, $changedNode),
                        'diff' => $diffArray
                    ];
                }
                // The && in belows condition is on purpose as creating a thumbnail for comparison only works
                // if actually BOTH are ImageInterface (or NULL).
            } elseif (
                ($originalPropertyValue instanceof ImageInterface || $originalPropertyValue === null)
                && ($changedPropertyValue instanceof ImageInterface || $changedPropertyValue === null)
            ) {
                $contentChanges[$propertyName] = [
                    'type' => 'image',
                    'propertyLabel' => $this->getPropertyLabel($propertyName, $changedNode),
                    'original' => $originalPropertyValue,
                    'changed' => $changedPropertyValue
                ];
            } elseif (
                $originalPropertyValue instanceof AssetInterface
                || $changedPropertyValue instanceof AssetInterface
            ) {
                $contentChanges[$propertyName] = [
                    'type' => 'asset',
                    'propertyLabel' => $this->getPropertyLabel($propertyName, $changedNode),
                    'original' => $originalPropertyValue,
                    'changed' => $changedPropertyValue
                ];
            } elseif ($originalPropertyValue instanceof \DateTime || $changedPropertyValue instanceof \DateTime) {
                $changed = false;
                if (!$changedPropertyValue instanceof \DateTime || !$originalPropertyValue instanceof \DateTime) {
                    $changed = true;
                } elseif ($changedPropertyValue->getTimestamp() !== $originalPropertyValue->getTimestamp()) {
                    $changed = true;
                }
                if ($changed) {
                    $contentChanges[$propertyName] = [
                        'type' => 'datetime',
                        'propertyLabel' => $this->getPropertyLabel($propertyName, $changedNode),
                        'original' => $originalPropertyValue,
                        'changed' => $changedPropertyValue
                    ];
                }
            }
        }
        return $contentChanges;
    }

    /**
     * Renders a slimmed down representation of a property of the given node. The output will be HTML, but does not
     * contain any markup from the original content.
     *
     * Note: It's clear that this method needs to be extracted and moved to a more universal service at some point.
     * However, since we only implemented diff-view support for this particular controller at the moment, it stays
     * here for the time being. Once we start displaying diffs elsewhere, we should refactor the diff rendering part.
     *
     * @param mixed $propertyValue
     * @return string
     */
    protected function renderSlimmedDownContent($propertyValue)
    {
        $content = '';
        if (is_string($propertyValue)) {
            $contentSnippet = preg_replace('/<br[^>]*>/', "\n", $propertyValue) ?: '';
            $contentSnippet = preg_replace('/<[^>]*>/', ' ', $contentSnippet) ?: '';
            $contentSnippet = str_replace('&nbsp;', ' ', $contentSnippet) ?: '';
            $content = trim(preg_replace('/ {2,}/', ' ', $contentSnippet) ?: '');
        }
        return $content;
    }

    /**
     * Tries to determine a label for the specified property
     *
     * @param string $propertyName
     * @param Node $changedNode
     * @return string
     */
    protected function getPropertyLabel($propertyName, Node $changedNode)
    {
        $properties = $this->getNodeType($changedNode)->getProperties();
        if (
            !isset($properties[$propertyName])
            || !isset($properties[$propertyName]['ui']['label'])
        ) {
            return $propertyName;
        }
        return $properties[$propertyName]['ui']['label'];
    }

    /**
     * A workaround for some missing functionality in the Diff Renderer:
     *
     * This method will check if content in the given diff array is either completely new or has been completely
     * removed and wraps the respective part in <ins> or <del> tags, because the Diff Renderer currently does not
     * do that in these cases.
     *
     * @param array<int|string,mixed> &$diffArray
     * @return void
     */
    protected function postProcessDiffArray(array &$diffArray): void
    {
        foreach ($diffArray as $index => $blocks) {
            foreach ($blocks as $blockIndex => $block) {
                $baseLines = trim(implode('', $block['base']['lines']), " \t\n\r\0\xC2\xA0");
                $changedLines = trim(implode('', $block['changed']['lines']), " \t\n\r\0\xC2\xA0");
                if ($baseLines === '') {
                    foreach ($block['changed']['lines'] as $lineIndex => $line) {
                        $diffArray[$index][$blockIndex]['changed']['lines'][$lineIndex] = '<ins>' . $line . '</ins>';
                    }
                }
                if ($changedLines === '') {
                    foreach ($block['base']['lines'] as $lineIndex => $line) {
                        $diffArray[$index][$blockIndex]['base']['lines'][$lineIndex] = '<del>' . $line . '</del>';
                    }
                }
            }
        }
    }

    /**
     * Creates an array of workspace names and their respective titles which are possible base workspaces for other
     * workspaces.
     * If $excludedWorkspace is set, this workspace and all its base workspaces will be excluded from the list of returned workspaces
     *
     * @param ContentRepository $contentRepository
     * @param WorkspaceName|null $excludedWorkspace
     * @return array<string,?string>
     */
    protected function prepareBaseWorkspaceOptions(
        ContentRepository $contentRepository,
        WorkspaceName $excludedWorkspace = null,
    ): array {
        $user = $this->userService->getCurrentUser();
        $baseWorkspaceOptions = [];
        $workspaces = $contentRepository->findWorkspaces();
        foreach ($workspaces as $workspace) {
            if (
                $excludedWorkspace !== null) {
                if ($workspace->workspaceName->equals($excludedWorkspace)) {
                    continue;
                }
                if ( $workspaces->getBaseWorkspaces($workspace->workspaceName)->get(
                        $excludedWorkspace
                    ) !== null) {
                    continue;
                }
            }
            $workspaceMetadata = $this->workspaceService->getWorkspaceMetadata($contentRepository->id, $workspace->workspaceName);
            if (!in_array($workspaceMetadata->classification, [WorkspaceClassification::SHARED, WorkspaceClassification::ROOT], true)) {
                continue;
            }
            if ($user === null) {
                continue;
            }
            $permissions = $this->workspaceService->getWorkspacePermissionsForUser($contentRepository->id, $workspace->workspaceName, $user);
            if (!$permissions->manage) {
                continue;
            }
            $baseWorkspaceOptions[$workspace->workspaceName->value] = $workspaceMetadata->title->value;
        }

        // Sort the base workspaces by title, but make sure the live workspace is always on top
        uksort($baseWorkspaceOptions, static function (string $a, string $b) {
            if ($a === 'live') {
                return -1;
            }
            if ($b === 'live') {
                return 1;
            }
            return strcasecmp($a, $b);
        });

        return $baseWorkspaceOptions;
    }

    /**
     * Creates an array of user names and their respective labels which are possible owners for a workspace.
     *
     * @return array<int|string,string>
     */
    protected function prepareOwnerOptions(): array
    {
        $ownerOptions = ['' => '-'];
        foreach ($this->userService->getUsers() as $user) {
            /** @var User $user */
            $ownerOptions[$this->persistenceManager->getIdentifierByObject($user)] = $user->getLabel();
        }

        return $ownerOptions;
    }

    private function getBaseWorkspaceWhenSureItExists(
        Workspace $workspace,
        ContentRepository $contentRepository,
    ): Workspace {
        /** @var WorkspaceName $baseWorkspaceName We expect this to exist */
        $baseWorkspaceName = $workspace->baseWorkspaceName;
        /** @var Workspace $baseWorkspace We expect this to exist */
        $baseWorkspace = $contentRepository->findWorkspaceByName($baseWorkspaceName);

        return $baseWorkspace;
    }

    /**
     * @param array<int|string,mixed> $arguments
     */
    public function getModuleLabel(string $id, array $arguments = [], mixed $quantity = null): string
    {
        return $this->translator->translateById(
            $id,
            $arguments,
            $quantity,
            null,
            'Main',
            'Neos.Workspace.Ui'
        ) ?: $id;
    }

    protected function getUserWorkspace(ContentRepository $contentRepository): Workspace
    {
        /*
        $items = [];
        $allWorkspaces = $contentRepository->findWorkspaces();
        foreach ($allWorkspaces as $workspace) {
            $workspaceMetadata = $this->workspaceService->getWorkspaceMetadata($contentRepositoryId, $workspace->workspaceName);
            $permissions = $this->workspaceService->getWorkspacePermissionsForUser($contentRepositoryId, $workspace->workspaceName, $currentUser);
            if (!$permissions->read) {
                continue;
            }
            $items[] = new WorkspaceListItem(
                name: $workspace->workspaceName->value,
                classification: $workspaceMetadata->classification->name,
                title: $workspaceMetadata->title->value,
                description: $workspaceMetadata->description->value,
                baseWorkspaceName: $workspace->baseWorkspaceName?->value,
                pendingChanges: $this->computePendingChanges($workspace, $contentRepository),
                hasDependantWorkspaces: !$allWorkspaces->getDependantWorkspaces($workspace->workspaceName)->isEmpty(),
                permissions: $permissions,
            );
        }
        */

        $currentUser = $this->userService->getCurrentUser();
        if ($currentUser === null) {
            throw new \RuntimeException('No user is authenticated', 1729505338);
        }
        $userWorkspace = $this->workspaceService->getPersonalWorkspaceForUser($contentRepository->id, $currentUser->getId());
        return $userWorkspace;
    }

    protected function getWorkspacesAndChangeCounts(
        Workspace $userWorkspace,
        ContentRepository $contentRepository
    ): WorkspaceDetailsCollection {
        $workspaceMetadata = $this->workspaceService->getWorkspaceMetadata($contentRepository->id, $userWorkspace->workspaceName);

        $workspacesAndCounts = [];
        $workspacesAndCounts[$userWorkspace->workspaceName->value] = new WorkspaceDetails(
            $userWorkspace,
            $workspaceMetadata->ownerUserId ? $this->userService->findUserById(
                UserId::fromString($workspaceMetadata->ownerUserId->value)
            )?->getLabel() : null,
            $this->computePendingChanges($userWorkspace, $contentRepository),
        );

        $allWorkspaces = $contentRepository->findWorkspaces();

        foreach ($allWorkspaces as $workspace) {
            $workspacesPermissions = $this->workspaceService->getWorkspacePermissionsForUser(
                $contentRepository->id,
                $workspace->workspaceName,
                $this->userService->getCurrentUser()
            );
            if (!$workspacesPermissions->manage || !$workspacesPermissions->read) { // todo check corrrect?
                continue;
            }
            $workspacesAndCounts[$workspace->workspaceName->value] = new WorkspaceDetails(
                $workspace,
                $workspaceMetadata->ownerUserId ? $this->userService->findUserById(
                    UserId::fromString($workspaceMetadata->ownerUserId->value)
                )?->getLabel() : null,
                $this->computePendingChanges($workspace, $contentRepository),
                $allWorkspaces->getDependantWorkspaces($workspace->workspaceName)->count(),
                $workspacesPermissions->write,
                $workspacesPermissions->manage // todo always true????
            );
        }
        return new WorkspaceDetailsCollection($workspacesAndCounts);
    }
}
