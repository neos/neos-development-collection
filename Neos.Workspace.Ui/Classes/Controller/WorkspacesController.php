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

use Doctrine\DBAL\DBALException;
use JsonException;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdsToPublishOrDiscard;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Dto\NodeIdToPublishOrDiscard;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Exception\WorkspaceAlreadyExists;
use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\FindAncestorNodesFilter;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Mvc\Exception\StopActionException;
use Neos\Flow\Mvc\View\JsonView;
use Neos\Flow\Security\Authorization\PrivilegeManagerInterface;
use Neos\Neos\Domain\Model\SiteNodeName;
use Neos\Neos\Domain\Repository\UserRepository;
use Neos\Neos\Domain\Service\NodeTypeNameFactory;
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
use Neos\Diff\Diff;
use Neos\Diff\Renderer\Html\HtmlArrayRenderer;
use Neos\Neos\Controller\Module\ModuleTranslationTrait;
use Neos\Flow\Annotations as Flow;
use Neos\Error\Messages\Message;
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
use Neos\Neos\Utility\NodeTypeWithFallbackProvider;
use Neos\Neos\Domain\Service\WorkspaceNameBuilder;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;

/**
 * The Neos Workspaces module controller
 */
#[Flow\Scope('singleton')]
class WorkspacesController extends AbstractModuleController
{
    use ModuleTranslationTrait;
    use NodeTypeWithFallbackProvider;

    protected $viewFormatToObjectNameMap = [
        'json' => JsonView::class,
    ];

    #[Flow\Inject]
    protected ContentRepositoryRegistry $contentRepositoryRegistry;

    #[Flow\Inject]
    protected SiteRepository $siteRepository;

    #[Flow\Inject]
    protected PropertyMapper $propertyMapper;

    #[Flow\Inject]
    protected Context $securityContext;

    #[Flow\Inject]
    protected UserService $domainUserService;

    #[Flow\Inject]
    protected PackageManager $packageManager;

    #[Flow\Inject]
    protected PrivilegeManagerInterface $privilegeManager;

    #[Flow\Inject]
    protected UserRepository $userRepository;

    // TODO: Readd or replace when ACL is implemented via the Workspace model
//    #[Flow\Inject]
//    protected WorkspaceDetailsRepository $workspaceDetailsRepository;

    /**
     * Displays a list of workspaces
     */
    public function indexAction(): void
    {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $currentAccount = $this->securityContext->getAccount();
        $userWorkspaceName = WorkspaceNameBuilder::fromAccountIdentifier($currentAccount->getAccountIdentifier());
        $userWorkspace = $contentRepository->getWorkspaceFinder()->findOneByName($userWorkspaceName);

        $workspaceData = [
            $userWorkspace->workspaceName->value => $this->getWorkspaceInfo($userWorkspace, $contentRepository),
        ];

        foreach ($contentRepository->getWorkspaceFinder()->findAll() as $workspace) {
            if ($this->userCanAccessWorkspace($workspace)) {
                $workspaceData[$workspace->workspaceName->value] = $this->getWorkspaceInfo(
                    $workspace,
                    $contentRepository
                );
            }
        }

        $this->view->assignMultiple([
            'userWorkspace' => $userWorkspace->workspaceName->value,
            'baseWorkspaceOptions' => $this->prepareBaseWorkspaceOptions($contentRepository),
            'userCanManageInternalWorkspaces' => $this->privilegeManager->isPrivilegeTargetGranted(
                'Neos.Neos:Backend.Module.Management.Workspaces.ManageInternalWorkspaces'
            ),
            'userList' => $this->prepareOwnerOptions(),
            'workspaces' => $workspaceData,
            'csrfToken' => $this->securityContext->getCsrfProtectionToken(),
            'validation' => $this->settings['validation'],
            'flashMessages' => $this->controllerContext->getFlashMessageContainer()->getMessagesAndFlush(),
        ]);
    }

    public function showAction(WorkspaceName $workspace): void
    {
        $this->validateWorkspaceAccess($workspace);
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $workspacesControllerInternals = $this->contentRepositoryRegistry->buildService(
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
            'selectedWorkspaceLabel' => $workspaceObj->workspaceTitle,
            'baseWorkspaceName' => $workspaceObj->baseWorkspaceName,
            'baseWorkspaceLabel' => $workspaceObj->baseWorkspaceName, // TODO fallback to title
            // TODO $this->domainUserService->currentUserCanPublishToWorkspace($workspace->getBaseWorkspace()),
            'canPublishToBaseWorkspace' => true,
            'siteChanges' => $this->computeSiteChanges($workspaceObj, $contentRepository),
            'contentDimensions' => $workspacesControllerInternals->getContentDimensionsOrderedByPriority()
        ]);
    }

    /**
     * Create a workspace with a unique name
     *
     * @Flow\Validate(argumentName="title", type="\Neos\Flow\Validation\Validator\NotEmptyValidator")
     * @param WorkspaceTitle $title Human friendly title of the workspace, for example "Christmas Campaign"
     * @param WorkspaceName $baseWorkspace Workspace the new workspace should be based on
     * @param string $visibility Visibility of the new workspace, must be either "internal" or "shared"
     * @param WorkspaceDescription $description A description explaining the purpose of the new workspace
     */
    public function createAction(
        WorkspaceTitle $title,
        WorkspaceName $baseWorkspace,
        string $visibility,
        WorkspaceDescription $description,
    ): void {
        $success = false;
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $newWorkspace = null;
        $workspace = $contentRepository->getWorkspaceFinder()->findOneByTitle($title);

        if ($workspace instanceof Workspace) {
            $this->addFlashMessage(
                $this->getModuleLabel('workspaces.workspaceWithThisTitleAlreadyExists'),
                '',
                Message::SEVERITY_WARNING
            );
        } else {
            // If a workspace with the generated name already exists, try again with a new name
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

            // The user should never be null at this point but the type system doesn't know that
            $currentUserIdentifier = $this->domainUserService->getCurrentUserIdentifier();
            if (is_null($currentUserIdentifier)) {
                throw new \InvalidArgumentException('Cannot create workspace without a current user', 1652155039);
            }

            try {
                $contentRepository->handle(
                    CreateWorkspace::create(
                        $workspaceName,
                        $baseWorkspace,
                        $title,
                        $description,
                        ContentStreamId::create(),
                        ($visibility === 'private' || !$this->userCanManageInternalWorkspaces(
                            )) ? $currentUserIdentifier : null
                    )
                )->block();

                $newWorkspace = $contentRepository->getWorkspaceFinder()->findOneByName($workspaceName);
                $this->addFlashMessage(
                    $this->getModuleLabel(
                        'message.workspaceCreated',
                        ['workspaceName' => $newWorkspace->workspaceTitle?->value]
                    ),
                );
                $success = true;
            } catch (WorkspaceAlreadyExists) {
                $this->addFlashMessage(
                    $this->getModuleLabel('workspaces.workspaceWithThisTitleAlreadyExists'),
                    '',
                    Message::SEVERITY_WARNING
                );
            }
        }

        $this->view->assign('value', [
            'success' => $success,
            'messages' => $this->controllerContext->getFlashMessageContainer()->getMessagesAndFlush(),
            'workspace' => $newWorkspace ? $this->getWorkspaceInfo($newWorkspace, $contentRepository) : [],
            // Include a new list of base workspace options which might contain the new workspace depending on its visibility
            'baseWorkspaceOptions' => $this->prepareBaseWorkspaceOptions($contentRepository),
        ]);
    }

    /**
     * Update a workspace
     *
     * @Flow\Validate(argumentName="title", type="\Neos\Flow\Validation\Validator\NotEmptyValidator")
     * @param WorkspaceName $workspaceName
     * @param WorkspaceTitle $title Human friendly title of the workspace, for example "Christmas Campaign"
     * @param WorkspaceDescription $description A description explaining the purpose of the new workspace
     * @param ?string $workspaceOwner Id of the owner of the workspace
     */
    public function updateAction(
        WorkspaceName $workspaceName,
        WorkspaceTitle $title,
        WorkspaceDescription $description,
        ?string $workspaceOwner
    ): void {
        $success = false;

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
        } elseif (!$this->validateWorkspaceChain($workspace, $contentRepository->getWorkspaceFinder())) {
            $this->addFlashMessage(
                $this->getModuleLabel(
                    'error.invalidWorkspaceChain',
                    ['workspaceName' => $workspace->workspaceTitle]
                ),
                '',
                Message::SEVERITY_ERROR
            );
        } else {
            if (!$workspace->workspaceTitle->equals($title) || !$workspace->workspaceDescription->equals(
                    $description
                )) {
                $contentRepository->handle(
                    RenameWorkspace::create(
                        $workspaceName,
                        $title,
                        $description
                    )
                )->block();
            }

            if ($workspace->workspaceOwner !== $workspaceOwner) {
                $contentRepository->handle(
                    ChangeWorkspaceOwner::create(
                        $workspaceName,
                        $workspaceOwner ?: null,
                    )
                )->block();
            }

            // TODO: Changing the base
            // TODO: Check & Reimplement
//            // Get or create workspace details
//            $workspaceDetails = $this->workspaceDetailsRepository->findOneByWorkspace($workspace);
//            if (!$workspaceDetails) {
//                $workspaceDetails = new WorkspaceDetails($workspace);
//                $this->workspaceDetailsRepository->add($workspaceDetails);
//            }
//
//            // Update access control list
//            $providedAcl = $this->request->hasArgument('acl') ? $this->request->getArgument('acl') ?? [] : [];
//            $acl = $workspace->workspaceOwner ? $providedAcl : [];
//            $allowedUsers = array_map(fn($userName) => $this->userRepository->findByIdentifier($userName), $acl);
//
//            // Rebase users if they were using the workspace but lost access by the update
//            $allowedAccounts = array_map(static fn(User $user) => (string)$user->getAccounts()->first()->getAccountIdentifier(), $allowedUsers);
//            $liveWorkspace = $contentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::forLive());
//            foreach ($workspaceDetails->getAcl() as $prevAcl) {
//                $aclAccount = $prevAcl->getAccounts()->first()->getAccountIdentifier();
//                if (!in_array($aclAccount, $allowedAccounts, true)) {
//                    $userWorkspace = $contentRepository->getWorkspaceFinder()->findOneByName(WorkspaceNameBuilder::fromAccountIdentifier($aclAccount));
//                    if ($userWorkspace && $userWorkspace->baseWorkspaceName->value === $workspace->workspaceName->value) {
//                        $contentRepository->handle(
//                            ChangeBaseWorkspace::create(
//                                $userWorkspace->workspaceName,
//                                $liveWorkspace->workspaceName,
//                            )
//                        );
//                    }
//                }
//            }
//
//            // Update workspace details
//            $workspaceDetails->setAcl($allowedUsers);
//            $this->workspaceDetailsRepository->update($workspaceDetails);
//            // TODO: Check if persist is still needed
//            //$this->persistenceManager->persistAll();

            $workspace = $contentRepository->getWorkspaceFinder()->findOneByName($workspaceName);
            $this->addFlashMessage($this->getModuleLabel('workspaces.workspaceHasBeenUpdated', [$title->value]));
            $success = true;
        }

        $this->view->assign('value', [
            'success' => $success,
            'messages' => $this->controllerContext->getFlashMessageContainer()->getMessagesAndFlush(),
            'workspace' => $workspace ? $this->getWorkspaceInfo($workspace, $contentRepository) : [],
            'baseWorkspaceOptions' => $this->prepareBaseWorkspaceOptions($contentRepository),
        ]);
    }

    /**
     * Delete a workspace and all contained unpublished changes.
     * Descendent workspaces will be rebased on the live workspace.
     *
     * @throws DBALException
     * @Flow\SkipCsrfProtection
     */
    public function deleteAction(WorkspaceName $workspaceName): void
    {
        $success = false;

        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        /** @var Workspace[] $rebasedWorkspaces */
        $rebasedWorkspaces = [];

        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName($workspaceName);
        if ($workspace === null) {
            $this->addFlashMessage(
                $this->getModuleLabel('workspaces.workspaceDoesNotExist'),
                '',
                Message::SEVERITY_ERROR
            );
        } elseif ($workspace->isPersonalWorkspace()) {
            $this->addFlashMessage(
                $this->getModuleLabel(
                    'message.workspaceIsPersonal',
                    ['workspaceName' => $workspace->workspaceTitle->value]
                ),
                '',
                Message::SEVERITY_ERROR
            );
        } else {
//            $liveWorkspace = $contentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::forLive());

            // Fetch and delete dependent workspaces for target workspace
            $dependentWorkspaces = $contentRepository->getWorkspaceFinder()->findByBaseWorkspace(
                $workspace->workspaceName
            );

//            // TODO: Can we rebase a workspace with unpublished nodes? Will that be prone to rebase errors?
//            // TODO: Adjust to new API
//            foreach ($dependentWorkspaces as $dependentWorkspace) {
//                $dependentWorkspace->setBaseWorkspace($liveWorkspace);
//                //$this->workspaceRepository->update($dependentWorkspace);
//                $this->addFlashMessage(
//                    $this->translateById('message.workspaceRebased',
//                        [
//                            'dependentWorkspaceName' => $dependentWorkspace->getTitle(),
//                            'workspaceName' => $workspace->getTitle(),
//                        ]
//                    )
//                    , '', Message::SEVERITY_WARNING);
//                $rebasedWorkspaces[] = $dependentWorkspace;
//            }

            if (!empty($dependentWorkspaces)) {
                $dependentWorkspaceTitles = array_map(static fn($workspace) => $workspace->workspaceName->value,
                    $dependentWorkspaces);

                $this->addFlashMessage(
                    $this->getModuleLabel(
                        'workspaces.workspaceCannotBeDeletedBecauseOfDependencies',
                        [$workspace->workspaceTitle?->value, implode(', ', $dependentWorkspaceTitles)]
                    ),
                    '',
                    Message::SEVERITY_ERROR
                );
            } else {
                /** @var ChangeFinder $changeFinder */
                $changeFinder = $contentRepository->projectionState(ChangeFinder::class);
                $unpublishedNodes = $changeFinder->countByContentStreamId($workspace->currentContentStreamId);

                if ($unpublishedNodes > 0) {
                    $contentRepository->handle(
                        DiscardWorkspace::create($workspaceName)
                    )->block();
                }

                $contentRepository->handle(
                    DeleteWorkspace::create($workspaceName)
                )->block();

//                $workspaceDetails = $this->workspaceDetailsRepository->findOneByWorkspace($workspace);
//                if ($workspaceDetails) {
//                    $this->workspaceDetailsRepository->remove($workspaceDetails);
//                }

                $this->addFlashMessage(
                    $this->getModuleLabel(
                        'message.workspaceRemoved',
                        [
                            'workspaceName' => $workspace->workspaceTitle?->value,
                            'unpublishedNodes' => $unpublishedNodes,
                            'dependentWorkspaces' => 0,
                        ]
                    )
                );
                $success = true;
            }
        }

        $this->view->assign('value', [
            'success' => $success,
            'messages' => $this->controllerContext->getFlashMessageContainer()->getMessagesAndFlush(),
            'rebasedWorkspaces' => array_map(static fn($workspace) => $workspace->workspaceName->value,
                $rebasedWorkspaces),
        ]);
    }

    /**
     * Rebase the current users personal workspace onto the given $targetWorkspace and then
     * redirects to the $targetNode in the content module.
     * @throws StopActionException
     */
    public function rebaseAndRedirectAction(Node $targetNode, Workspace $targetWorkspace): void
    {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $currentAccount = $this->securityContext->getAccount();
        $personalWorkspaceName = WorkspaceNameBuilder::fromAccountIdentifier($currentAccount->getAccountIdentifier());
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

        $this->addFlashMessage(
            $this->translator->translateById(
                'workspaces.selectedChangeHasBeenPublished',
                [],
                null,
                null,
                'Modules',
                'Neos.Neos'
            ) ?: 'workspaces.selectedChangeHasBeenPublished'
        );
        $this->redirect('show', null, null, ['workspace' => $selectedWorkspace->value]);
    }

    /**
     * Discard a a single node
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

        $this->addFlashMessage(
            $this->translator->translateById(
                'workspaces.selectedChangeHasBeenDiscarded',
                [],
                null,
                null,
                'Modules',
                'Neos.Neos'
            ) ?: 'workspaces.selectedChangeHasBeenDiscarded'
        );
        $this->redirect('show', null, null, ['workspace' => $selectedWorkspace->value]);
    }

    /**
     * @psalm-param list<string> $nodes
     * @throws StopActionException
     */
    public function publishOrDiscardNodesAction(array $nodes, string $action, string $selectedWorkspace): void
    {
        $selectedWorkspace = WorkspaceName::fromString($selectedWorkspace);
        $this->validateWorkspaceAccess($selectedWorkspace);
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
                $this->addFlashMessage($this->getModuleLabel('workspaces.selectedChangesHaveBeenPublished'));
                break;
            case 'discard':
                $command = DiscardIndividualNodesFromWorkspace::create(
                    $selectedWorkspace,
                    NodeIdsToPublishOrDiscard::create(...$nodesToPublishOrDiscard),
                );
                $contentRepository->handle($command)
                    ->block();
                $this->addFlashMessage($this->getModuleLabel('workspaces.selectedChangesHaveBeenDiscarded'));
                break;
            default:
                throw new \RuntimeException('Invalid action "' . htmlspecialchars($action) . '" given.', 1346167441);
        }

        $this->redirect('show', null, null, ['workspace' => $selectedWorkspace->value]);
    }

    /**
     * Publishes the whole workspace
     * @throws StopActionException
     */
    public function publishWorkspaceAction(WorkspaceName $selectedWorkspace): void
    {
        $this->validateWorkspaceAccess($selectedWorkspace);
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $contentRepository->handle(
            PublishWorkspace::create(
                $selectedWorkspace
            )
        )->block();
        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName($selectedWorkspace);
        /** @var Workspace $workspace Otherwise the command handler would have thrown an exception */
        /** @var WorkspaceName $baseWorkspaceName Otherwise the command handler would have thrown an exception */
        $baseWorkspaceName = $workspace->baseWorkspaceName;
        $this->addFlashMessage(
            $this->getModuleLabel(
                'workspaces.allChangesInWorkspaceHaveBeenPublished',
                [
                    htmlspecialchars($selectedWorkspace->value),
                    htmlspecialchars($baseWorkspaceName->value)
                ]
            )
        );
        $this->redirect('index');
    }

    /**
     * Discards content of the whole workspace
     * @throws StopActionException
     */
    public function discardWorkspaceAction(WorkspaceName $workspace): void
    {
        $this->validateWorkspaceAccess($workspace);
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())
            ->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $contentRepository->handle(
            DiscardWorkspace::create(
                $workspace,
            )
        )->block();

        $this->addFlashMessage(
            $this->getModuleLabel(
                'workspaces.allChangesInWorkspaceHaveBeenDiscarded',
                [htmlspecialchars($workspace->value)],
            )
        );
        $this->redirect('index');
    }

    public function getChangesAction(): void
    {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $currentAccount = $this->securityContext->getAccount();
        $userWorkspaceName = WorkspaceNameBuilder::fromAccountIdentifier($currentAccount->getAccountIdentifier());
        $userWorkspace = $contentRepository->getWorkspaceFinder()->findOneByName($userWorkspaceName);

        $workspaces = $contentRepository->getWorkspaceFinder()->findAll();

        $changesByWorkspace = [
            $userWorkspace->workspaceName->value => $this->computeChangesCount($userWorkspace, $contentRepository),
        ];
        foreach ($workspaces as $workspace) {
            if ($this->userCanAccessWorkspace($workspace)) {
                $changesByWorkspace[$workspace->workspaceName->value] = $this->computeChangesCount(
                    $workspace,
                    $contentRepository
                );
            }
        }

        $this->view->assign('value', ['changesByWorkspace' => $changesByWorkspace]);
    }

    /**
     * Computes the number of added, changed and removed nodes for the given workspace
     *
     * @return array<string,int>
     * @throws JsonException
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
     * @throws JsonException
     */
    protected function computeSiteChanges(Workspace $selectedWorkspace, ContentRepository $contentRepository): array
    {
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
                $ancestors = Nodes::fromArray([$node])->merge($ancestors);

                $nodePathSegments = [];
                $documentPathSegments = [];
                foreach ($ancestors as $ancestor) {
                    $pathSegment = $ancestor->nodeName ?: NodeName::fromString($ancestor->nodeAggregateId->value);
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
                if ($documentNode !== null && $siteNode !== null && $siteNode->nodeName) {
                    $siteNodeName = $siteNode->nodeName->value;
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

                    // As for changes of type `delete` we are using nodes from the live content stream
                    // we can't create `serializedNodeAddress` from the node.
                    // Instead, we use the original stored values.
                    $nodeAddress = new NodeAddress(
                        $change->contentStreamId,
                        $change->originDimensionSpacePoint->toDimensionSpacePoint(),
                        $change->nodeAggregateId,
                        $selectedWorkspace->workspaceName
                    );

                    $change = [
                        'node' => $node,
                        'serializedNodeAddress' => $nodeAddress->serializeForUri(),
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
        ContentStreamId $baseContentStreamId,
        ContentRepository $contentRepository,
    ): ?Node {
        $baseSubgraph = $contentRepository->getContentGraph()->getSubgraph(
            $baseContentStreamId,
            $modifiedNode->subgraphIdentity->dimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );
        return $baseSubgraph->findNodeById($modifiedNode->nodeAggregateId);
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
     */
    protected function renderSlimmedDownContent(mixed $propertyValue): string
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
     */
    protected function getPropertyLabel(string $propertyName, Node $changedNode): string
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
     * @return array<string,?string>
     */
    protected function prepareBaseWorkspaceOptions(
        ContentRepository $contentRepository,
        Workspace $excludedWorkspace = null,
    ): array {
        $baseWorkspaceOptions = [];
        $workspaces = $contentRepository->getWorkspaceFinder()->findAll();
        foreach ($workspaces as $workspace) {
            if (
                $workspace !== $excludedWorkspace
                && !$workspace->isPersonalWorkspace()
                && ($workspace->isPublicWorkspace()
                    || $workspace->isInternalWorkspace()
                    || $this->domainUserService->currentUserCanManageWorkspace($workspace))
                && (!$excludedWorkspace
                    || $workspaces->getBaseWorkspaces($workspace->workspaceName)->get(
                        $excludedWorkspace->workspaceName
                    ) === null)
            ) {
                $baseWorkspaceOptions[$workspace->workspaceName->value] = $workspace->workspaceTitle->value;
            }
        }
        asort($baseWorkspaceOptions, SORT_FLAG_CASE | SORT_NATURAL);

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
        asort($ownerOptions, SORT_FLAG_CASE | SORT_NATURAL);

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

    protected function getWorkspaceInfo(Workspace $workspace, ContentRepository $contentRepository): array
    {
        $owner = $workspace->workspaceOwner ?
            $this->domainUserService->findByUserIdentifier(UserId::fromString($workspace->workspaceOwner)) :
            null;
        $creator = $creatorName = $lastChangedDate = $lastChangedBy = $lastChangedTimestamp = $isStale = null;
        $acl = [];

        if ($owner && $workspace->isPrivateWorkspace()) {
            $creator = $owner;
            $creatorName = $owner->getLabel();
        }

//        $workspaceDetails = $this->workspaceDetailsRepository->findOneByWorkspace($workspace);
//        if ($workspaceDetails) {
//            $creator = $workspaceDetails->getCreator();
//            if ($creator) {
//                $creatorUser = $this->userService->getUser($creator);
//                $creatorName = $creatorUser ? $creatorUser->getLabel() : $creator;
//            }
//            $isStale = !$workspace->isPersonalWorkspace() && $workspaceDetails->getLastChangedDate() && $workspaceDetails->getLastChangedDate()->getTimestamp() < time() - $this->settings['staleTime'];
//
//            if ($workspaceDetails->getLastChangedBy()) {
//                $lastChangedBy = $this->userService->getUser($workspaceDetails->getLastChangedBy());
//            }
//            $lastChangedDate = $workspaceDetails->getLastChangedDate() ? $workspaceDetails->getLastChangedDate()->format('c') : null;
//            $lastChangedTimestamp = $workspaceDetails->getLastChangedDate() ? $workspaceDetails->getLastChangedDate()->getTimestamp() : null;
//            $acl = $workspaceDetails->getAcl() ?? [];
//        }

        $baseWorkspace = null;
        if ($workspace->baseWorkspaceName !== null) {
            $baseWorkspace = $contentRepository->getWorkspaceFinder()->findOneByName($workspace->baseWorkspaceName);
        }

        // TODO: Introduce a DTO for this
        return [
            'name' => $workspace->workspaceName->value,
            'title' => $workspace->workspaceTitle?->value ?: '',
            'description' => $workspace->workspaceDescription,
            'owner' => $owner ? [
                'id' => $workspace->workspaceOwner,
                'label' => $owner->getLabel(),
            ] : null,
            'baseWorkspace' => $baseWorkspace ? [
                'name' => $baseWorkspace->workspaceName->value,
                'title' => $baseWorkspace->workspaceTitle?->value ?: '',
            ] : null,
            //'nodeCount' => $workspace->getNodeCount(),
            'nodeCount' => 0,
            'changesCounts' => null, // Will be retrieved async by the UI to speed up module loading time
            'isPersonal' => $workspace->isPersonalWorkspace(),
            'isInternal' => $workspace->isInternalWorkspace(),
            'isStale' => $isStale,
            'canPublish' => $this->domainUserService->currentUserCanPublishToWorkspace($workspace),
            'canManage' => $this->domainUserService->currentUserCanManageWorkspace($workspace),
            //'dependentWorkspacesCount' => count($this->workspaceRepository->findByBaseWorkspace($workspace)),
            'dependentWorkspacesCount' => 0,
            'creator' => $creator ? [
                'id' => $creator,
                'label' => $creatorName,
            ] : null,
            'lastChangedDate' => $lastChangedDate,
            'lastChangedTimestamp' => $lastChangedTimestamp,
            'lastChangedBy' => $lastChangedBy ? [
                'id' => $this->getUserId($lastChangedBy),
                'label' => $lastChangedBy->getLabel(),
            ] : null,
            'acl' => array_map(fn(User $user) => [
                'id' => $this->getUserId($user),
                'label' => $user->getLabel(),
            ], $acl),
        ];
    }

    /**
     * Checks whether the current user can access the given workspace.
     * The check via the `userService` is modified via an aspect to allow access to the workspace if the
     * workspace is specifically allowed for the user.
     */
    protected function userCanAccessWorkspace(Workspace $workspace): bool
    {
        return !$workspace->workspaceName->isLive() && ($workspace->isInternalWorkspace(
                ) || $this->domainUserService->currentUserCanReadWorkspace($workspace));
    }

    private function userCanManageInternalWorkspaces(): bool
    {
        return $this->privilegeManager->isPrivilegeTargetGranted(
            'Neos.Neos:Backend.Module.Management.Workspaces.ManageInternalWorkspaces'
        );
    }

    /**
     * @throws StopActionException
     */
    protected function validateWorkspaceAccess(WorkspaceName $workspaceName = null): void
    {
        $contentRepositoryId = SiteDetectionResult::fromRequest($this->request->getHttpRequest())->contentRepositoryId;
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName($workspaceName);

        if ($workspace && !$this->userCanAccessWorkspace($workspace)) {
            $this->getModuleLabel('error.workspaceInaccessible', ['workspaceName' => $workspaceName->value]);
            $this->redirect('index');
        }
    }

    /**
     * Checks whether a workspace base workspace chain can be fully resolved without circular references
     */
    protected function validateWorkspaceChain(Workspace $workspace, WorkspaceFinder $workspaceFinder): bool
    {
        $baseWorkspaces = [$workspace->workspaceName->value];
        $currentWorkspace = $workspace;
        while ($currentWorkspace->baseWorkspaceName && $currentWorkspace = $workspaceFinder->findOneByName(
                $currentWorkspace->baseWorkspaceName
            )) {
            if (in_array($currentWorkspace->workspaceName->value, $baseWorkspaces, true)) {
                return false;
            }
            $baseWorkspaces[] = $currentWorkspace->workspaceName->value;
        }
        return true;
    }

    protected function getUserId(User $user): string
    {
        return $this->persistenceManager->getIdentifierByObject($user);
    }
}
