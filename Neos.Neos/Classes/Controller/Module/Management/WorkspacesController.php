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

namespace Neos\Neos\Controller\Module\Management;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdsToPublishOrDiscard;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Exception\WorkspaceAlreadyExists;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindAncestorNodesFilter;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\Flow\I18n\Exception\IndexOutOfBoundsException;
use Neos\Flow\I18n\Exception\InvalidFormatPlaceholderException;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Neos\Domain\Model\SiteNodeName;
use Neos\Neos\PendingChangesProjection\ChangeFinder;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\RenameWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\DeleteWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\ChangeWorkspaceOwner;
use Neos\Neos\FrontendRouting\NodeAddress;
use Neos\Neos\FrontendRouting\NodeAddressFactory;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Diff\Diff;
use Neos\Diff\Renderer\Html\HtmlArrayRenderer;
use Neos\Neos\Controller\Module\ModuleTranslationTrait;
use Neos\Neos\Domain\Model\WorkspaceName as NeosWorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\Error\Messages\Message;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Security\Context;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;

/**
 * The Neos Workspaces module controller
 *
 * @Flow\Scope("singleton")
 */
class WorkspacesController extends AbstractModuleController
{
    use ModuleTranslationTrait;

    /**
     * @Flow\Inject
     * @var SiteRepository
     */
    protected $siteRepository;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @Flow\Inject
     * @var Context
     */
    protected $securityContext;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $domainUserService;

    /**
     * @var PackageManager
     * @Flow\Inject
     */
    protected $packageManager;

    /**
     * @var ContentRepositoryRegistry
     * @Flow\Inject
     */
    protected $contentRepositoryRegistry;

    /**
     * Display a list of unpublished content
     *
     * @return void
     */
    public function indexAction()
    {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $currentAccount = $this->securityContext->getAccount();
        $userWorkspace = $contentRepository->getWorkspaceFinder()->findOneByName(
            NeosWorkspaceName::fromAccountIdentifier($currentAccount->getAccountIdentifier())
                ->toContentRepositoryWorkspaceName()
        );
        if (is_null($userWorkspace)) {
            throw new \RuntimeException('Current user has no workspace', 1645485990);
        }

        $workspacesAndCounts = [
            $userWorkspace->workspaceName->value => [
                'workspace' => $userWorkspace,
                'changesCounts' => $this->computeChangesCount($userWorkspace, $contentRepository),
                'canPublish' => false,
                'canManage' => false,
                'canDelete' => false,
                'workspaceOwnerHumanReadable' => $userWorkspace->workspaceOwner ? $this->domainUserService->findByUserIdentifier(UserId::fromString($userWorkspace->workspaceOwner))?->getLabel() : null
            ]
        ];

        foreach ($contentRepository->getWorkspaceFinder()->findAll() as $workspace) {
            /** @var \Neos\ContentRepository\Core\Projection\Workspace\Workspace $workspace */
            // FIXME: This check should be implemented through a specialized Workspace Privilege or something similar
            if (!$workspace->isPersonalWorkspace() && ($workspace->isInternalWorkspace() || $this->domainUserService->currentUserCanManageWorkspace($workspace))) {
                $workspaceName = $workspace->workspaceName->value;
                $workspacesAndCounts[$workspaceName]['workspace'] = $workspace;
                $workspacesAndCounts[$workspaceName]['changesCounts'] =
                    $this->computeChangesCount($workspace, $contentRepository);
                $workspacesAndCounts[$workspaceName]['canPublish']
                    = $this->domainUserService->currentUserCanPublishToWorkspace($workspace);
                $workspacesAndCounts[$workspaceName]['canManage']
                    = $this->domainUserService->currentUserCanManageWorkspace($workspace);
                $workspacesAndCounts[$workspaceName]['dependentWorkspacesCount'] = count(
                    $contentRepository->getWorkspaceFinder()->findByBaseWorkspace($workspace->workspaceName)
                );
                $workspacesAndCounts[$workspaceName]['workspaceOwnerHumanReadable'] = $workspace->workspaceOwner ? $this->domainUserService->findByUserIdentifier(UserId::fromString($workspace->workspaceOwner))?->getLabel() : null;
            }
        }

        $this->view->assign('userWorkspace', $userWorkspace);
        $this->view->assign('workspacesAndChangeCounts', $workspacesAndCounts);
    }


    public function showAction(WorkspaceName $workspace): void
    {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $workspacesControllerInternals = $this->contentRepositoryRegistry->getService(
            $contentRepositoryId,
            new WorkspacesControllerInternalsFactory()
        );

        $workspaceObj = $contentRepository->getWorkspaceFinder()->findOneByName($workspace);
        if (is_null($workspaceObj)) {
            /** @todo add flash message */
            $this->redirect('index');
            return;
        }
        $this->view->assignMultiple([
            'selectedWorkspace' => $workspaceObj,
            'selectedWorkspaceLabel' => $workspaceObj->workspaceTitle ?: $workspaceObj->workspaceName,
            'baseWorkspaceName' => $workspaceObj->baseWorkspaceName,
            'baseWorkspaceLabel' => $workspaceObj->baseWorkspaceName, // TODO fallback to title
            // TODO $this->domainUserService->currentUserCanPublishToWorkspace($workspace->getBaseWorkspace()),
            'canPublishToBaseWorkspace' => true,
            'siteChanges' => $this->computeSiteChanges($workspaceObj, $contentRepository),
            'contentDimensions' => $workspacesControllerInternals->getContentDimensionsOrderedByPriority()
        ]);
    }

    /**
     * @return void
     */
    public function newAction()
    {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $this->view->assign('baseWorkspaceOptions', $this->prepareBaseWorkspaceOptions($contentRepository));
    }

    /**
     * Create a workspace
     *
     * @Flow\Validate(argumentName="title", type="\Neos\Flow\Validation\Validator\NotEmptyValidator")
     * @param WorkspaceTitle $title Human friendly title of the workspace, for example "Christmas Campaign"
     * @param WorkspaceName $baseWorkspace Workspace the new workspace should be based on
     * @param string $visibility Visibility of the new workspace, must be either "internal" or "shared"
     * @param WorkspaceDescription $description A description explaining the purpose of the new workspace
     * @return void
     * @throws \Neos\Flow\Mvc\Exception\StopActionException
     */
    public function createAction(
        WorkspaceTitle $title,
        WorkspaceName $baseWorkspace,
        string $visibility,
        WorkspaceDescription $description,
    ) {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $workspaceName = WorkspaceName::fromString(
            WorkspaceName::transliterateFromString($title->value)->value . '-'
            . substr(base_convert(microtime(false), 10, 36), -5, 5)
        );
        while ($contentRepository->getWorkspaceFinder()->findOneByName($workspaceName) instanceof Workspace) {
            $workspaceName = WorkspaceName::fromString(
                WorkspaceName::transliterateFromString($title->value)->value . '-'
                . substr(base_convert(microtime(false), 10, 36), -5, 5)
            );
        }

        $currentUserIdentifier = $this->domainUserService->getCurrentUserIdentifier();
        if (is_null($currentUserIdentifier)) {
            throw new \InvalidArgumentException('Cannot create workspace without a current user', 1652155039);
        }

        try {
            $contentRepository->handle(
                new CreateWorkspace(
                    $workspaceName,
                    $baseWorkspace,
                    $title,
                    $description,
                    ContentStreamId::create(),
                    $visibility === 'private' ? $currentUserIdentifier : null
                )
            )->block();
        } catch (WorkspaceAlreadyExists $exception) {
            $this->addFlashMessage(
                $this->getModuleLabel('workspaces.workspaceWithThisTitleAlreadyExists'),
                '',
                Message::SEVERITY_WARNING
            );
            $this->redirect('new');
        }

        $this->redirect('index');
    }

    /**
     * Edit a workspace
     *
     * @param WorkspaceName $workspaceName
     * @return void
     */
    public function editAction(WorkspaceName $workspaceName)
    {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName($workspaceName);
        if (is_null($workspace)) {
            // @todo add flash message
            $this->redirect('index');
            return;
        }
        $this->view->assign('workspace', $workspace);
        $this->view->assign('baseWorkspaceOptions', $this->prepareBaseWorkspaceOptions($contentRepository, $workspace));
        // TODO: $this->view->assign('disableBaseWorkspaceSelector',
        // $this->publishingService->getUnpublishedNodesCount($workspace) > 0);
        $this->view->assign(
            'showOwnerSelector',
            $this->domainUserService->currentUserCanTransferOwnershipOfWorkspace($workspace)
        );
        $this->view->assign('ownerOptions', $this->prepareOwnerOptions());
    }

    /**
     * Update a workspace
     *
     * @Flow\Validate(argumentName="title", type="\Neos\Flow\Validation\Validator\NotEmptyValidator")
     * @param WorkspaceName $workspaceName
     * @param WorkspaceTitle $title Human friendly title of the workspace, for example "Christmas Campaign"
     * @param WorkspaceDescription $description A description explaining the purpose of the new workspace
     * @param string $workspaceOwner Id of the owner of the workspace
     * @return void
     */
    public function updateAction(WorkspaceName $workspaceName, WorkspaceTitle $title, WorkspaceDescription $description, ?string $workspaceOwner)
    {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        if ($title->value === '') {
            $title = WorkspaceTitle::fromString($workspaceName->value);
        }

        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName($workspaceName);
        if ($workspace === null) {
            $this->addFlashMessage(
                $this->getModuleLabel('workspaces.workspaceDoesNotExist'),
                '',
                Message::SEVERITY_ERROR
            );
            $this->redirect('index');
            return;
        }

        if (!$workspace->workspaceTitle?->equals($title) || !$workspace->workspaceDescription->equals($description)) {
            $contentRepository->handle(
                new RenameWorkspace(
                    $workspaceName,
                    $title,
                    $description
                )
            )->block();
        }

        if ($workspace->workspaceOwner !== $workspaceOwner) {
            $contentRepository->handle(
                new ChangeWorkspaceOwner(
                    $workspaceName,
                    $workspaceOwner ?: null,
                )
            )->block();
        }

        $this->addFlashMessage($this->translator->translateById(
            'workspaces.workspaceHasBeenUpdated',
            [$title->value],
            null,
            null,
            'Modules',
            'Neos.Neos'
        ) ?: 'workspaces.workspaceHasBeenUpdated');
        $this->redirect('index');
    }

    /**
     * Delete a workspace
     *
     * @param WorkspaceName $workspaceName A workspace to delete
     * @return void
     */
    public function deleteAction(WorkspaceName $workspaceName)
    {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName($workspaceName);
        if ($workspace === null) {
            $this->addFlashMessage(
                $this->getModuleLabel('workspaces.workspaceDoesNotExist'),
                '',
                Message::SEVERITY_ERROR
            );
            $this->redirect('index');
            return;
        }

        if ($workspace->isPersonalWorkspace()) {
            $this->redirect('index');
            return;
        }

        $dependentWorkspaces = $contentRepository->getWorkspaceFinder()
            ->findByBaseWorkspace($workspace->workspaceName);
        if (count($dependentWorkspaces) > 0) {
            $dependentWorkspaceTitles = [];
            /** @var Workspace $dependentWorkspace */
            foreach ($dependentWorkspaces as $dependentWorkspace) {
                $dependentWorkspaceTitles[] = $dependentWorkspace->workspaceTitle?->value;
            }

            $message = $this->translator->translateById(
                'workspaces.workspaceCannotBeDeletedBecauseOfDependencies',
                [$workspace->workspaceTitle?->value, implode(', ', $dependentWorkspaceTitles)],
                null,
                null,
                'Modules',
                'Neos.Neos'
            ) ?: 'workspaces.workspaceCannotBeDeletedBecauseOfDependencies';
            $this->addFlashMessage($message, '', Message::SEVERITY_WARNING);
            $this->redirect('index');
        }

        $nodesCount = 0;

        try {
            $nodesCount = $contentRepository->projectionState(ChangeFinder::class)
                ->countByContentStreamId(
                    $workspace->currentContentStreamId
                );
        } catch (\Exception $exception) {
            $message = $this->translator->translateById(
                'workspaces.notDeletedErrorWhileFetchingUnpublishedNodes',
                [$workspace->workspaceTitle?->value],
                null,
                null,
                'Modules',
                'Neos.Neos'
            ) ?: 'workspaces.notDeletedErrorWhileFetchingUnpublishedNodes';
            $this->addFlashMessage($message, '', Message::SEVERITY_WARNING);
            $this->redirect('index');
        }
        if ($nodesCount > 0) {
            $message = $this->translator->translateById(
                'workspaces.workspaceCannotBeDeletedBecauseOfUnpublishedNodes',
                [$workspace->workspaceTitle?->value, $nodesCount],
                $nodesCount,
                null,
                'Modules',
                'Neos.Neos'
            ) ?: 'workspaces.workspaceCannotBeDeletedBecauseOfUnpublishedNodes';
            $this->addFlashMessage($message, '', Message::SEVERITY_WARNING);
            $this->redirect('index');
        }

        $contentRepository->handle(
            new DeleteWorkspace(
                $workspaceName,
            )
        )->block();

        $this->addFlashMessage($this->translator->translateById(
            'workspaces.workspaceHasBeenRemoved',
            [$workspace->workspaceTitle?->value],
            null,
            null,
            'Modules',
            'Neos.Neos'
        ) ?: 'workspaces.workspaceHasBeenRemoved');
        $this->redirect('index');
    }

    /**
     * Rebase the current users personal workspace onto the given $targetWorkspace and then
     * redirects to the $targetNode in the content module.
     */
    public function rebaseAndRedirectAction(Node $targetNode, Workspace $targetWorkspace): void
    {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $currentAccount = $this->securityContext->getAccount();
        $personalWorkspaceName = NeosWorkspaceName::fromAccountIdentifier($currentAccount->getAccountIdentifier())
            ->toContentRepositoryWorkspaceName();
        $personalWorkspace = $contentRepository->getWorkspaceFinder()->findOneByName($personalWorkspaceName);
        /** @var Workspace $personalWorkspace */

        /** @todo do something else
         * if ($personalWorkspace !== $targetWorkspace) {
         * if ($this->publishingService->getUnpublishedNodesCount($personalWorkspace) > 0) {
         * $message = $this->translator->translateById(
         * 'workspaces.cantEditBecauseWorkspaceContainsChanges',
         * [],
         * null,
         * null,
         * 'Modules',
         * 'Neos.Neos'
         * ) ?: 'workspaces.cantEditBecauseWorkspaceContainsChanges';
         * $this->addFlashMessage($message, '', Message::SEVERITY_WARNING, [], 1437833387);
         * $this->redirect('show', null, null, ['workspace' => $targetWorkspace]);
         * }
         * $personalWorkspace->setBaseWorkspace($targetWorkspace);
         * $this->workspaceFinder->update($personalWorkspace);
         * }
         */

        $targetNodeAddressInPersonalWorkspace = new NodeAddress(
            $personalWorkspace->currentContentStreamId,
            $targetNode->subgraphIdentity->dimensionSpacePoint,
            $targetNode->nodeAggregateId,
            $personalWorkspace->workspaceName
        );

        $mainRequest = $this->controllerContext->getRequest()->getMainRequest();
        /** @var ActionRequest $mainRequest */
        $this->uriBuilder->setRequest($mainRequest);

        if ($this->packageManager->isPackageAvailable('Neos.Neos.Ui')) {
            $this->redirect(
                'index',
                'Backend',
                'Neos.Neos.Ui',
                ['node' => $targetNodeAddressInPersonalWorkspace]
            );
        }

        $this->redirect(
            'show',
            'Frontend\\Node',
            'Neos.Neos',
            ['node' => $targetNodeAddressInPersonalWorkspace]
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
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $nodeAddressFactory = NodeAddressFactory::create($contentRepository);
        $nodeAddress = $nodeAddressFactory->createFromUriString($nodeAddress);

        $command = PublishIndividualNodesFromWorkspace::create(
            $selectedWorkspace,
            NodeIdsToPublishOrDiscard::create(
                new NodeIdToPublishOrDiscard(
                    $nodeAddress->contentStreamId,
                    $nodeAddress->nodeAggregateId,
                    $nodeAddress->dimensionSpacePoint
                )
            ),
        );
        $contentRepository->handle($command)
            ->block();

        $this->addFlashMessage($this->translator->translateById(
            'workspaces.selectedChangeHasBeenPublished',
            [],
            null,
            null,
            'Modules',
            'Neos.Neos'
        ) ?: 'workspaces.selectedChangeHasBeenPublished');
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
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $nodeAddressFactory = NodeAddressFactory::create($contentRepository);
        $nodeAddress = $nodeAddressFactory->createFromUriString($nodeAddress);

        $command = DiscardIndividualNodesFromWorkspace::create(
            $selectedWorkspace,
            NodeIdsToPublishOrDiscard::create(
                new NodeIdToPublishOrDiscard(
                    $nodeAddress->contentStreamId,
                    $nodeAddress->nodeAggregateId,
                    $nodeAddress->dimensionSpacePoint
                )
            ),
        );
        $contentRepository->handle($command)
            ->block();

        $this->addFlashMessage($this->translator->translateById(
            'workspaces.selectedChangeHasBeenDiscarded',
            [],
            null,
            null,
            'Modules',
            'Neos.Neos'
        ) ?: 'workspaces.selectedChangeHasBeenDiscarded');
        $this->redirect('show', null, null, ['workspace' => $selectedWorkspace->value]);
    }

    /**
     * Publishes or discards the given nodes
     *
     * @param array $nodes
     * @throws \Exception
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    /** @phpstan-ignore-next-line
     * @param array $nodes
     * @param string $action
     * @param string $selectedWorkspace
     * @throws IndexOutOfBoundsException
     * @throws InvalidFormatPlaceholderException
     * @throws StopActionException
     */
    public function publishOrDiscardNodesAction(array $nodes, string $action, string $selectedWorkspace): void
    {
        $selectedWorkspace = WorkspaceName::fromString($selectedWorkspace);
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $nodeAddressFactory = NodeAddressFactory::create($contentRepository);

        $nodesToPublishOrDiscard = [];
        foreach ($nodes as $node) {
            $nodeAddress = $nodeAddressFactory->createFromUriString($node);
            $nodesToPublishOrDiscard[] = new NodeIdToPublishOrDiscard(
                $nodeAddress->contentStreamId,
                $nodeAddress->nodeAggregateId,
                $nodeAddress->dimensionSpacePoint
            );
        }

        switch ($action) {
            case 'publish':
                $command = PublishIndividualNodesFromWorkspace::create(
                    $selectedWorkspace,
                    NodeIdsToPublishOrDiscard::create(...$nodesToPublishOrDiscard),
                );
                $contentRepository->handle($command)
                    ->block();
                $this->addFlashMessage($this->translator->translateById(
                    'workspaces.selectedChangesHaveBeenPublished',
                    [],
                    null,
                    null,
                    'Modules',
                    'Neos.Neos'
                ) ?: 'workspaces.selectedChangesHaveBeenPublished');
                break;
            case 'discard':
                $command = DiscardIndividualNodesFromWorkspace::create(
                    $selectedWorkspace,
                    NodeIdsToPublishOrDiscard::create(...$nodesToPublishOrDiscard),
                );
                $contentRepository->handle($command)
                    ->block();
                $this->addFlashMessage($this->translator->translateById(
                    'workspaces.selectedChangesHaveBeenDiscarded',
                    [],
                    null,
                    null,
                    'Modules',
                    'Neos.Neos'
                ) ?: 'workspaces.selectedChangesHaveBeenDiscarded');
                break;
            default:
                throw new \RuntimeException('Invalid action "' . htmlspecialchars($action) . '" given.', 1346167441);
        }

        $this->redirect('show', null, null, ['workspace' => $selectedWorkspace->value]);
    }

    /**
     * Publishes the whole workspace
     */
    public function publishWorkspaceAction(WorkspaceName $workspace): void
    {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $contentRepository->handle(
            new PublishWorkspace(
                $workspace
            )
        )->block();
        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName($workspace);
        /** @var Workspace $workspace Otherwise the command handler would have thrown an exception */
        /** @var WorkspaceName $baseWorkspaceName Otherwise the command handler would have thrown an exception */
        $baseWorkspaceName = $workspace->baseWorkspaceName;
        $this->addFlashMessage($this->translator->translateById(
            'workspaces.allChangesInWorkspaceHaveBeenPublished',
            [
                htmlspecialchars($workspace->workspaceName->value),
                htmlspecialchars($baseWorkspaceName->value)
            ],
            null,
            null,
            'Modules',
            'Neos.Neos'
        ) ?: 'workspaces.allChangesInWorkspaceHaveBeenPublished');
        $this->redirect('index');
    }

    /**
     * Discards content of the whole workspace
     *
     * @param WorkspaceName $workspace
     */
    public function discardWorkspaceAction(WorkspaceName $workspace): void
    {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $contentRepository->handle(
            DiscardWorkspace::create(
                $workspace,
            )
        )->block();

        $this->addFlashMessage($this->translator->translateById(
            'workspaces.allChangesInWorkspaceHaveBeenDiscarded',
            [htmlspecialchars($workspace->value)],
            null,
            null,
            'Modules',
            'Neos.Neos'
        ) ?: 'workspaces.allChangesInWorkspaceHaveBeenDiscarded');
        $this->redirect('index');
    }

    /**
     * Computes the number of added, changed and removed nodes for the given workspace
     *
     * @return array<string,int>
     */
    protected function computeChangesCount(Workspace $selectedWorkspace, ContentRepository $contentRepository): array
    {
        $changesCount = ['new' => 0, 'changed' => 0, 'removed' => 0, 'total' => 0];
        foreach ($this->computeSiteChanges($selectedWorkspace, $contentRepository) as $siteChanges) {
            foreach ($siteChanges['documents'] as $documentChanges) {
                foreach ($documentChanges['changes'] as $change) {
                    if ($change['isRemoved'] === true) {
                        $changesCount['removed']++;
                    } elseif ($change['isNew']) {
                        $changesCount['new']++;
                    } else {
                        $changesCount['changed']++;
                    };
                    $changesCount['total']++;
                }
            }
        }

        return $changesCount;
    }

    /**
     * Builds an array of changes for sites in the given workspace
     * @return array<string,mixed>
     */
    protected function computeSiteChanges(Workspace $selectedWorkspace, ContentRepository $contentRepository): array
    {
        $nodeAddressFactory = NodeAddressFactory::create($contentRepository);

        $siteChanges = [];
        $changes = $contentRepository->projectionState(ChangeFinder::class)
            ->findByContentStreamId(
                $selectedWorkspace->currentContentStreamId
            );

        foreach ($changes as $change) {
            $contentStreamId = $change->contentStreamId;

            if ($change->deleted) {
                // If we deleted a node, there is no way for us to anymore find the deleted node in the ContentStream
                // where the node was deleted.
                // Thus, to figure out the rootline for display, we check the *base workspace* Content Stream.
                //
                // This is safe because the UI basically shows what would be removed once the deletion is published.
                $baseWorkspace = $this->getBaseWorkspaceWhenSureItExists($selectedWorkspace, $contentRepository);
                $contentStreamId = $baseWorkspace->currentContentStreamId;
            }
            $subgraph = $contentRepository->getContentGraph()->getSubgraph(
                $contentStreamId,
                $change->originDimensionSpacePoint->toDimensionSpacePoint(),
                VisibilityConstraints::withoutRestrictions()
            );

            $node = $subgraph->findNodeById($change->nodeAggregateId);
            if ($node) {
                $documentNode = null;
                $siteNode = null;
                $ancestors = $subgraph->findAncestorNodes(
                    $node->nodeAggregateId,
                    FindAncestorNodesFilter::create()
                );
                $nodePathSegments = [];
                $documentPathSegments = [];
                foreach ($ancestors as $ancestor) {
                    $pathSegment = $ancestor->nodeName ?: NodeName::fromString($ancestor->nodeAggregateId->value);
                    $nodePathSegments[] = $pathSegment;
                    if ($ancestor->nodeType->isOfType('Neos.Neos:Document')) {
                        $documentPathSegments[] = $pathSegment;
                        if (is_null($documentNode)) {
                            $documentNode = $ancestor;
                        }
                        // the site node is the last ancestor of type Document
                        $siteNode = $documentNode;
                    }
                }

                // Neither $documentNode, $siteNode or its cannot really be null, this is just for type checks;
                // We should probably throw an exception though
                if ($documentNode !== null && $siteNode !== null && $siteNode->nodeName) {
                    $siteNodeName = $siteNode->nodeName->value;
                    $documentPath = implode('/', array_slice(array_map(
                        fn (NodeName $nodeName): string => $nodeName->value,
                        $documentPathSegments
                    ), 2));
                    $relativePath = str_replace(
                        sprintf('//%s/%s', $siteNodeName, $documentPath),
                        '',
                        implode('/', array_map(
                            fn (NodeName $nodeName): string => $nodeName->value,
                            $nodePathSegments
                        ))
                    );
                    if (!isset($siteChanges[$siteNodeName]['siteNode'])) {
                        $siteChanges[$siteNodeName]['siteNode']
                            = $this->siteRepository->findOneByNodeName(SiteNodeName::fromString($siteNodeName));
                    }
                    $siteChanges[$siteNodeName]['documents'][$documentPath]['documentNode'] = $documentNode;

                    $change = [
                        'node' => $node,
                        'serializedNodeAddress' => $nodeAddressFactory->createFromNode($node)->serializeForUri(),
                        'isRemoved' => $change->deleted,
                        'isNew' => false,
                        'contentChanges' => $this->renderContentChanges(
                            $node,
                            $change->contentStreamId,
                            $contentRepository
                        )
                    ];
                    if ($node->nodeType->isOfType('Neos.Neos:Node')) {
                        $change['configuration'] = $node->nodeType->getFullConfiguration();
                    }
                    $siteChanges[$siteNodeName]['documents'][$documentPath]['changes'][$relativePath] = $change;
                }
            }
        }

        ksort($siteChanges);
        foreach ($siteChanges as $siteKey => $site) {
            /*foreach ($site['documents'] as $documentKey => $document) {
                $liveDocumentNode = $liveContext->getNodeByIdentifier($document['documentNode']->getIdentifier());
                $siteChanges[$siteKey]['documents'][$documentKey]['isMoved']
                    = $liveDocumentNode && $document['documentNode']->getPath() !== $liveDocumentNode->getPath();
                $siteChanges[$siteKey]['documents'][$documentKey]['isNew'] = $liveDocumentNode === null;
                foreach ($document['changes'] as $changeKey => $change) {
                    $liveNode = $liveContext->getNodeByIdentifier($change['node']->getIdentifier());
                    $siteChanges[$siteKey]['documents'][$documentKey]['changes'][$changeKey]['isNew']
                        = is_null($liveNode);
                    $siteChanges[$siteKey]['documents'][$documentKey]['changes'][$changeKey]['isMoved']
                        = $liveNode && $change['node']->getPath() !== $liveNode->getPath();
                }
            }*/
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
        ContentStreamId $baseContentStreamId,
        ContentRepository $contentRepository,
    ): ?Node {
        $baseSubgraph = $contentRepository->getContentGraph()->getSubgraph(
            $baseContentStreamId,
            $modifiedNode->subgraphIdentity->dimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );
        $node = $baseSubgraph->findNodeById($modifiedNode->nodeAggregateId);

        return $node;
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
        $currentWorkspace = $contentRepository->getWorkspaceFinder()->findOneByCurrentContentStreamId(
            $contentStreamIdOfOriginalNode
        );
        $originalNode = null;
        if ($currentWorkspace !== null) {
            $baseWorkspace = $this->getBaseWorkspaceWhenSureItExists($currentWorkspace, $contentRepository);
            $baseContentStreamId = $baseWorkspace->currentContentStreamId;
            $originalNode = $this->getOriginalNode($changedNode, $baseContentStreamId, $contentRepository);
        }


        $contentChanges = [];

        $changeNodePropertiesDefaults = $changedNode->nodeType->getDefaultValuesForProperties();

        $renderer = new HtmlArrayRenderer();
        foreach ($changedNode->properties as $propertyName => $changedPropertyValue) {
            if (
                $originalNode === null && empty($changedPropertyValue)
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
        $properties = $changedNode->nodeType->getProperties();
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
     * If $excludedWorkspace is set, this workspace and all its child workspaces will be excluded from the list of returned workspaces
     *
     * @param ContentRepository $contentRepository
     * @param Workspace|null $excludedWorkspace
     * @return array<string,?string>
     */
    protected function prepareBaseWorkspaceOptions(
        ContentRepository $contentRepository,
        Workspace $excludedWorkspace = null,
    ): array {
        $baseWorkspaceOptions = [];
        $workspaces = $contentRepository->getWorkspaceFinder()->findAll();
        foreach ($workspaces as $workspace) {

            /** @var Workspace $workspace */
            if (
                !$workspace->isPersonalWorkspace()
                && $workspace !== $excludedWorkspace
                && ($workspace->isPublicWorkspace()
                    || $workspace->isInternalWorkspace()
                    || $this->domainUserService->currentUserCanManageWorkspace($workspace))
                && (!$excludedWorkspace || $workspaces->getBaseWorkspaces($workspace->workspaceName)->get($excludedWorkspace->workspaceName) === null)
            ) {
                $baseWorkspaceOptions[$workspace->workspaceName->value] = $workspace->workspaceTitle?->value;
            }
        }

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
        foreach ($this->domainUserService->getUsers() as $user) {
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
        $baseWorkspace = $contentRepository->getWorkspaceFinder()->findOneByName($baseWorkspaceName);

        return $baseWorkspace;
    }
}
