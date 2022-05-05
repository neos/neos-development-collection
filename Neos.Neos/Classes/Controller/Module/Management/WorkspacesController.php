<?php
namespace Neos\Neos\Controller\Module\Management;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\DimensionSpace\Dimension\ContentDimensionSourceInterface;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\Feature\WorkspaceCommandHandler;
use Neos\ContentRepository\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Feature\WorkspaceDiscarding\Command\DiscardIndividualNodesFromWorkspace;
use Neos\ContentRepository\Feature\WorkspaceDiscarding\Command\DiscardWorkspace;
use Neos\ContentRepository\Feature\WorkspacePublication\Command\PublishIndividualNodesFromWorkspace;
use Neos\ContentRepository\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\Projection\Changes\ChangeFinder;
use Neos\ContentRepository\Projection\Content\ContentGraphInterface;
use Neos\ContentRepository\SharedModel\NodeAddress;
use Neos\ContentRepository\SharedModel\NodeAddressFactory;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\VisibilityConstraints;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceTitle;
use Neos\ContentRepositoryRegistry\Utility;
use Neos\Diff\Diff;
use Neos\Diff\Renderer\Html\HtmlArrayRenderer;
use Neos\EventSourcedNeosAdjustments\Domain\Context\Workspace\WorkspaceName as NeosWorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\Error\Messages\Message;
use Neos\Flow\I18n\Translator;
use Neos\Flow\Mvc\ActionRequest;
use Neos\Flow\Package\PackageManager;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Property\TypeConverter\PersistentObjectConverter;
use Neos\Flow\Security\Context;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Neos\Controller\Module\AbstractModuleController;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Repository\SiteRepository;
use Neos\Neos\Domain\Service\UserService;
use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\Model\Workspace;
use Neos\ContentRepository\Exception\WorkspaceException;


/**
 * The Neos Workspaces module controller
 *
 * @Flow\Scope("singleton")
 */
class WorkspacesController extends AbstractModuleController
{
    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

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
    protected $userService;

    /**
     * @Flow\Inject
     * @var Translator
     */
    protected $translator;

    /**
     * @var PackageManager
     * @Flow\Inject
     */
    protected $packageManager;

    /**
     * @var ContentDimensionSourceInterface
     * @Flow\Inject
     */
    protected $contentDimensionSource;

    /**
     * @Flow\Inject
     * @var ChangeFinder
     */
    protected $changeFinder;

    /**
     * @Flow\Inject
     * @var ContentGraphInterface
     */
    protected $contentGraph;

    /**
     * @Flow\Inject
     * @var WorkspaceCommandHandler
     */
    protected $workspaceCommandHandler;

    /**
     * @Flow\Inject
     * @var RuntimeBlocker
     */
    protected $runtimeBlocker;

    /**
     * @return void
     */
    /*protected function initializeAction()
    {
        if ($this->arguments->hasArgument('node')) {
            $this->arguments->getArgument('node')->getPropertyMappingConfiguration()
                ->setTypeConverterOption(NodeConverter::class, NodeConverter::REMOVED_CONTENT_SHOWN, true);
        }
        if ($this->arguments->hasArgument('nodes')) {
            $this->arguments->getArgument('nodes')->getPropertyMappingConfiguration()->forProperty('*')
                ->setTypeConverterOption(NodeConverter::class, NodeConverter::REMOVED_CONTENT_SHOWN, true);
        }
        parent::initializeAction();
    }*/

    /**
     * Display a list of unpublished content
     *
     * @return void
     */
    public function indexAction()
    {
        $currentAccount = $this->securityContext->getAccount();
        $userWorkspace = $this->workspaceFinder->findOneByName(
            NeosWorkspaceName::fromAccountIdentifier($currentAccount->getAccountIdentifier())
                ->toContentRepositoryWorkspaceName()
        );
        if (is_null($userWorkspace)) {
            throw new \RuntimeException('Current user has no workspace', 1645485990);
        }

        $workspacesAndCounts = [
            $userWorkspace->getWorkspaceName()->jsonSerialize() => [
                'workspace' => $userWorkspace,
                'changesCounts' => $this->computeChangesCount($userWorkspace),
                'canPublish' => false,
                'canManage' => false,
                'canDelete' => false
            ]
        ];

        foreach ($this->workspaceFinder->findAll() as $workspace) {
            /** @var \Neos\ContentRepository\Projection\Workspace\Workspace $workspace */
            // FIXME: This check should be implemented through a specialized Workspace Privilege or something similar
            // TODO $this->userService->currentUserCanManageWorkspace($workspace)
            if (!$workspace->isPersonalWorkspace() && ($workspace->isInternalWorkspace())) {
                $workspaceName = (string)$workspace->getWorkspaceName();
                $workspacesAndCounts[$workspaceName]['workspace'] = $workspace;
                $workspacesAndCounts[$workspaceName]['changesCounts'] = $this->computeChangesCount($workspace);
                $workspacesAndCounts[$workspaceName]['canPublish']
                    = $this->userService->currentUserCanPublishToWorkspace($workspace);
                $workspacesAndCounts[$workspaceName]['canManage']
                    = $this->userService->currentUserCanManageWorkspace($workspace);
                $workspacesAndCounts[$workspaceName]['dependentWorkspacesCount']
                    = count($this->workspaceFinder->findByBaseWorkspace($workspace->getWorkspaceName()));
            }
        }

        $this->view->assign('userWorkspace', $userWorkspace);
        $this->view->assign('workspacesAndChangeCounts', $workspacesAndCounts);
    }


    public function showAction(WorkspaceName $workspace): void
    {
        $workspaceObj = $this->workspaceFinder->findOneByName($workspace);
        if (is_null($workspaceObj)) {
            /** @todo add flash message */
            $this->redirect('index');
        }
        /** @var Workspace $workspace */
        $this->view->assignMultiple([
            'selectedWorkspace' => $workspaceObj,
            'selectedWorkspaceLabel' => $workspaceObj->workspaceTitle ?: $workspaceObj->getWorkspaceName(),
            'baseWorkspaceName' => $workspaceObj->getBaseWorkspaceName(),
            'baseWorkspaceLabel' => $workspaceObj->getBaseWorkspaceName(), // TODO fallback to title
            // TODO $this->userService->currentUserCanPublishToWorkspace($workspace->getBaseWorkspace()),
            'canPublishToBaseWorkspace' => true,
            'siteChanges' => $this->computeSiteChanges($workspace),
            'contentDimensions' => $this->contentDimensionSource->getContentDimensionsOrderedByPriority()
        ]);
    }

    /**
     * @return void
     */
    public function newAction()
    {
        $this->view->assign('baseWorkspaceOptions', $this->prepareBaseWorkspaceOptions());
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
        WorkspaceDescription $description
    ) {
        // TODO
        //$workspace = $this->workspaceFinder->findOneByWorkspaceTitle($title);
        //if ($workspace instanceof Workspace) {
        //    $this->addFlashMessage($this->translator->translateById('workspaces.workspaceWithThisTitleAlreadyExists',
        // [], null, null, 'Modules', 'Neos.Neos'), '', Message::SEVERITY_WARNING);
        //    $this->redirect('new');
        //}

        $workspaceName = WorkspaceName::fromString(Utility::renderValidNodeName((string)$title) . '-' . substr(
                base_convert(microtime(false), 10, 36),
                -5,
                5
            ));
        while ($this->workspaceFinder->findOneByName($workspaceName) instanceof Workspace) {
            $workspaceName = WorkspaceName::fromString(Utility::renderValidNodeName((string)$title) . '-' . substr(
                    base_convert(microtime(false), 10, 36),
                    -5,
                    5
                ));
        }

        if ($visibility === 'private') {
            $owner = UserIdentifier::fromString($this->persistenceManager->getIdentifierByObject(
                $this->userService->getCurrentUser()
            ));
        } else {
            $owner = null;
        }

        $this->workspaceCommandHandler->handleCreateWorkspace(
            new CreateWorkspace(
                $workspaceName,
                $baseWorkspace,
                $title,
                $description,
                UserIdentifier::fromString(
                    $this->persistenceManager->getIdentifierByObject($this->userService->getCurrentUser())
                ),
                ContentStreamIdentifier::create(),
                $owner
            )
        )->blockUntilProjectionsAreUpToDate();

        $this->redirect('index');
    }

    /**
     * Edit a workspace
     *
     * @param WorkspaceName $workspace
     * @return void
     */
    public function editAction(WorkspaceName $workspace)
    {
        $workspace = $this->workspaceFinder->findOneByName($workspace);
        if (is_null($workspace)) {
            // @todo add flash message
            $this->redirect('index');
        }
        /** @var Workspace $workspace */
        $this->view->assign('workspace', $workspace);
        $this->view->assign('baseWorkspaceOptions', $this->prepareBaseWorkspaceOptions($workspace));
        // TODO: $this->view->assign('disableBaseWorkspaceSelector',
        // $this->publishingService->getUnpublishedNodesCount($workspace) > 0);
        $this->view->assign(
            'showOwnerSelector',
            $this->userService->currentUserCanTransferOwnershipOfWorkspace($workspace)
        );
        $this->view->assign('ownerOptions', $this->prepareOwnerOptions());
    }

    /**
     * @return void
     */
    protected function initializeUpdateAction()
    {
        $converter = new PersistentObjectConverter();
        $this->arguments->getArgument('workspace')->getPropertyMappingConfiguration()
            ->forProperty('owner')
            ->setTypeConverter($converter)
            ->setTypeConverterOption(
                PersistentObjectConverter::class,
                PersistentObjectConverter::CONFIGURATION_TARGET_TYPE,
                User::class
            );
        parent::initializeAction();
    }

    /**
     * Update a workspace
     *
     * @param Workspace $workspace A workspace to update
     * @return void
     */
    public function updateAction(Workspace $workspace)
    {
        #if ($workspace->getTitle() === '') {
        #    $workspace->setTitle($workspace->getName());
        #}
        #$this->workspaceFinder->update($workspace);
        $this->addFlashMessage($this->translator->translateById(
            'workspaces.workspaceHasBeenUpdated',
            [(string)$workspace->getWorkspaceTitle()],
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
     * @param Workspace $workspace A workspace to delete
     * @return void
     */
    public function deleteAction(Workspace $workspace)
    {
        if ($workspace->isPersonalWorkspace()) {
            $this->redirect('index');
        }

        $dependentWorkspaces = $this->workspaceFinder->findByBaseWorkspace($workspace->getWorkspaceName());
        if (count($dependentWorkspaces) > 0) {
            $dependentWorkspaceTitles = [];
            /** @var Workspace $dependentWorkspace */
            foreach ($dependentWorkspaces as $dependentWorkspace) {
                $dependentWorkspaceTitles[] = (string)$dependentWorkspace->getWorkspaceTitle();
            }

            $message = $this->translator->translateById(
                'workspaces.workspaceCannotBeDeletedBecauseOfDependencies',
                [(string)$workspace->getWorkspaceTitle(), implode(', ', $dependentWorkspaceTitles)],
                null,
                null,
                'Modules',
                'Neos.Neos'
            ) ?: 'workspaces.workspaceCannotBeDeletedBecauseOfDependencies';
            $this->addFlashMessage($message, '', Message::SEVERITY_WARNING);
            $this->redirect('index');
        }

        $nodesCount = 0;
        /** @todo something else
        try {
        $nodesCount = $this->publishingService->getUnpublishedNodesCount($workspace);
        } catch (\Exception $exception) {
        $message = $this->translator->translateById(
        'workspaces.notDeletedErrorWhileFetchingUnpublishedNodes',
        [(string)$workspace->getWorkspaceTitle()],
        null,
        null,
        'Modules',
        'Neos.Neos'
        ) ?: 'workspaces.notDeletedErrorWhileFetchingUnpublishedNodes';
        $this->addFlashMessage($message, '', Message::SEVERITY_WARNING);
        $this->redirect('index');
        }*/
        //if ($nodesCount > 0) {
        $message = $this->translator->translateById(
            'workspaces.workspaceCannotBeDeletedBecauseOfUnpublishedNodes',
            [(string)$workspace->getWorkspaceTitle(), $nodesCount],
            $nodesCount,
            null,
            'Modules',
            'Neos.Neos'
        ) ?: 'workspaces.workspaceCannotBeDeletedBecauseOfUnpublishedNodes';
        $this->addFlashMessage($message, '', Message::SEVERITY_WARNING);
        $this->redirect('index');
        //}

        //$this->workspaceFinder->remove($workspace);
        $this->addFlashMessage($this->translator->translateById(
            'workspaces.workspaceHasBeenRemoved',
            [(string)$workspace->getWorkspaceTitle()],
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
     *
     * @param \Neos\ContentRepository\Projection\Content\NodeInterface $targetNode
     * @param Workspace $targetWorkspace
     * @return void
     */
    public function rebaseAndRedirectAction(NodeInterface $targetNode, Workspace $targetWorkspace)
    {
        $currentAccount = $this->securityContext->getAccount();
        $personalWorkspaceName = NeosWorkspaceName::fromAccountIdentifier($currentAccount->getAccountIdentifier())
            ->toContentRepositoryWorkspaceName();
        $personalWorkspace = $this->workspaceFinder->findOneByName($personalWorkspaceName);
        /** @var Workspace $personalWorkspace */

        /** @todo do something else
        if ($personalWorkspace !== $targetWorkspace) {
        if ($this->publishingService->getUnpublishedNodesCount($personalWorkspace) > 0) {
        $message = $this->translator->translateById(
        'workspaces.cantEditBecauseWorkspaceContainsChanges',
        [],
        null,
        null,
        'Modules',
        'Neos.Neos'
        ) ?: 'workspaces.cantEditBecauseWorkspaceContainsChanges';
        $this->addFlashMessage($message, '', Message::SEVERITY_WARNING, [], 1437833387);
        $this->redirect('show', null, null, ['workspace' => $targetWorkspace]);
        }
        $personalWorkspace->setBaseWorkspace($targetWorkspace);
        $this->workspaceFinder->update($personalWorkspace);
        }
         */

        $targetNodeAddressInPersonalWorkspace = new NodeAddress(
            $personalWorkspace->getCurrentContentStreamIdentifier(),
            $targetNode->getDimensionSpacePoint(),
            $targetNode->getNodeAggregateIdentifier(),
            $personalWorkspace->getWorkspaceName()
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
     * @param NodeAddress $node
     * @param WorkspaceName $selectedWorkspace
     */
    public function publishNodeAction(NodeAddress $node, WorkspaceName $selectedWorkspace): void
    {
        $command = PublishIndividualNodesFromWorkspace::create(
            $selectedWorkspace,
            [$node],
            UserIdentifier::fromString($this->securityContext->getAccount()->getAccountIdentifier())
        );
        $this->workspaceCommandHandler->handlePublishIndividualNodesFromWorkspace($command)
            ->blockUntilProjectionsAreUpToDate();

        $this->addFlashMessage($this->translator->translateById(
            'workspaces.selectedChangeHasBeenPublished',
            [],
            null,
            null,
            'Modules',
            'Neos.Neos'
        ) ?: 'workspaces.selectedChangeHasBeenPublished');
        $this->redirect('show', null, null, ['workspace' => $selectedWorkspace->jsonSerialize()]);
    }

    /**
     * Discard a a single node
     *
     * @param NodeAddress $node
     * @param WorkspaceName $selectedWorkspace
     * @throws WorkspaceException
     */
    public function discardNodeAction(NodeAddress $node, WorkspaceName $selectedWorkspace): void
    {
        $command = DiscardIndividualNodesFromWorkspace::create(
            $selectedWorkspace,
            [$node],
            UserIdentifier::fromString($this->securityContext->getAccount()->getAccountIdentifier())
        );
        $this->workspaceCommandHandler->handleDiscardIndividualNodesFromWorkspace($command)
            ->blockUntilProjectionsAreUpToDate();

        $this->addFlashMessage($this->translator->translateById(
            'workspaces.selectedChangeHasBeenDiscarded',
            [],
            null,
            null,
            'Modules',
            'Neos.Neos'
        ) ?: 'workspaces.selectedChangeHasBeenDiscarded');
        $this->redirect('show', null, null, ['workspace' => $selectedWorkspace->jsonSerialize()]);
    }

    /**
     * @Flow\Inject
     * @var NodeAddressFactory
     */
    protected $nodeAddressFactory;

    /**
     * Publishes or discards the given nodes
     *
     * @param array $nodes
     * @throws \Exception
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    /** @phpstan-ignore-next-line */
    public function publishOrDiscardNodesAction(array $nodes, string $action, WorkspaceName $selectedWorkspace): void
    {
        $nodeAddresses = [];
        foreach ($nodes as $node) {
            $nodeAddresses[] = $this->nodeAddressFactory->createFromUriString($node);
        }
        switch ($action) {
            case 'publish':
                $command = PublishIndividualNodesFromWorkspace::create(
                    $selectedWorkspace,
                    $nodeAddresses,
                    UserIdentifier::fromString($this->securityContext->getAccount()->getAccountIdentifier())
                );
                $this->workspaceCommandHandler->handlePublishIndividualNodesFromWorkspace($command)
                    ->blockUntilProjectionsAreUpToDate();
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
                    $nodeAddresses,
                    UserIdentifier::fromString($this->securityContext->getAccount()->getAccountIdentifier())
                );
                $this->workspaceCommandHandler->handleDiscardIndividualNodesFromWorkspace($command)
                    ->blockUntilProjectionsAreUpToDate();
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

        $this->redirect('show', null, null, ['workspace' => $selectedWorkspace]);
    }

    /**
     * Publishes the whole workspace
     */
    public function publishWorkspaceAction(WorkspaceName $workspace): void
    {
        $this->workspaceCommandHandler->handlePublishWorkspace(
            new PublishWorkspace(
                $workspace,
                $this->getCurrentUserIdentifier()
            )
        )->blockUntilProjectionsAreUpToDate();
        $workspace = $this->workspaceFinder->findOneByName($workspace);
        /** @var Workspace $workspace Otherwise the command handler would have thrown an exception */
        /** @var WorkspaceName $baseWorkspaceName Otherwise the command handler would have thrown an exception */
        $baseWorkspaceName = $workspace->getBaseWorkspaceName();
        $this->addFlashMessage($this->translator->translateById(
            'workspaces.allChangesInWorkspaceHaveBeenPublished',
            [
                htmlspecialchars($workspace->getWorkspaceName()->name),
                htmlspecialchars($baseWorkspaceName->name)
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
        $this->workspaceCommandHandler->handleDiscardWorkspace(
            DiscardWorkspace::create(
                $workspace,
                $this->getCurrentUserIdentifier()
            )
        )->blockUntilProjectionsAreUpToDate();

        $this->addFlashMessage($this->translator->translateById(
            'workspaces.allChangesInWorkspaceHaveBeenDiscarded',
            [htmlspecialchars($workspace->name)],
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
    protected function computeChangesCount(Workspace $selectedWorkspace): array
    {
        $changesCount = ['new' => 0, 'changed' => 0, 'removed' => 0, 'total' => 0];
        foreach ($this->computeSiteChanges($selectedWorkspace) as $siteChanges) {
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
    protected function computeSiteChanges(Workspace $selectedWorkspace): array
    {
        $siteChanges = [];
        $changes = $this->changeFinder->findByContentStreamIdentifier(
            $selectedWorkspace->getCurrentContentStreamIdentifier()
        );

        foreach ($changes as $change) {
            $contentStreamIdentifier = $change->contentStreamIdentifier;

            if ($change->deleted) {
                // If we deleted a node, there is no way for us to anymore find the deleted node in the ContentStream
                // where the node was deleted.
                // Thus, to figure out the rootline for display, we check the *base workspace* Content Stream.
                //
                // This is safe because the UI basically shows what would be removed once the deletion is published.
                $baseWorkspace = $this->getBaseWorkspaceWhenSureItExists($selectedWorkspace);
                $contentStreamIdentifier = $baseWorkspace->getCurrentContentStreamIdentifier();
            }
            $subgraph = $this->contentGraph->getSubgraphByIdentifier(
                $contentStreamIdentifier,
                $change->originDimensionSpacePoint->toDimensionSpacePoint(),
                VisibilityConstraints::withoutRestrictions()
            );

            $node = $subgraph->findNodeByNodeAggregateIdentifier($change->nodeAggregateIdentifier);
            if ($node) {
                $pathParts = explode('/', (string)$subgraph->findNodePath($node->getNodeAggregateIdentifier()));
                if (count($pathParts) > 2) {
                    $siteNodeName = $pathParts[2];
                    $document = null;
                    $closestDocumentNode = $node;
                    while ($closestDocumentNode) {
                        if ($closestDocumentNode->getNodeType()->isOfType('Neos.Neos:Document')) {
                            $document = $closestDocumentNode;
                            break;
                        }
                        $closestDocumentNode = $subgraph->findParentNode(
                            $closestDocumentNode->getNodeAggregateIdentifier()
                        );
                    }

                    // $document will be null if we have a broken root line for this node.
                    // This actually should never happen, but currently can in some scenarios.
                    if ($document !== null) {
                        assert($document instanceof NodeInterface);
                        $documentPath = implode('/', array_slice(explode(
                            '/',
                            (string)$subgraph->findNodePath($document->getNodeAggregateIdentifier())
                        ), 3));
                        $relativePath = str_replace(
                            sprintf('//%s/%s', $siteNodeName, $documentPath),
                            '',
                            (string)$subgraph->findNodePath($node->getNodeAggregateIdentifier())
                        );
                        if (!isset($siteChanges[$siteNodeName]['siteNode'])) {
                            $siteChanges[$siteNodeName]['siteNode']
                                = $this->siteRepository->findOneByNodeName($siteNodeName);
                        }
                        $siteChanges[$siteNodeName]['documents'][$documentPath]['documentNode'] = $document;

                        $change = [
                            'node' => $node,
                            'isRemoved' => $change->deleted,
                            'isNew' => false,
                            'contentChanges' => $this->renderContentChanges($node, $change->contentStreamIdentifier)
                        ];
                        if ($node->getNodeType()->isOfType('Neos.Neos:Node')) {
                            $change['configuration'] = $node->getNodeType()->getFullConfiguration();
                        }
                        $siteChanges[$siteNodeName]['documents'][$documentPath]['changes'][$relativePath] = $change;
                    }
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
        NodeInterface $modifiedNode,
        ContentStreamIdentifier $baseContentStreamIdentifier
    ): ?NodeInterface {
        $baseSubgraph = $this->contentGraph->getSubgraphByIdentifier(
            $baseContentStreamIdentifier,
            $modifiedNode->getDimensionSpacePoint(),
            VisibilityConstraints::withoutRestrictions()
        );
        $node = $baseSubgraph->findNodeByNodeAggregateIdentifier($modifiedNode->getNodeAggregateIdentifier());

        return $node;
    }

    /**
     * Renders the difference between the original and the changed content of the given node and returns it, along
     * with meta information, in an array.
     *
     * @return array<string,mixed>
     */
    protected function renderContentChanges(
        NodeInterface $changedNode,
        ContentStreamIdentifier $contentStreamIdentifierOfOriginalNode
    ): array {
        $currentWorkspace = $this->workspaceFinder->findOneByCurrentContentStreamIdentifier(
            $contentStreamIdentifierOfOriginalNode
        );
        $originalNode = null;
        if ($currentWorkspace !== null) {
            $baseWorkspace = $this->getBaseWorkspaceWhenSureItExists($currentWorkspace);
            $baseContentStreamIdentifier = $baseWorkspace->getCurrentContentStreamIdentifier();
            $originalNode = $this->getOriginalNode($changedNode, $baseContentStreamIdentifier);
        }


        $contentChanges = [];

        $changeNodePropertiesDefaults = $changedNode->getNodeType()->getDefaultValuesForProperties();

        $renderer = new HtmlArrayRenderer();
        foreach ($changedNode->getProperties() as $propertyName => $changedPropertyValue) {
            if ($originalNode === null && empty($changedPropertyValue)
                || (isset($changeNodePropertiesDefaults[$propertyName])
                    && $changedPropertyValue === $changeNodePropertiesDefaults[$propertyName])) {
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
            } elseif (($originalPropertyValue instanceof ImageInterface || $originalPropertyValue === null)
                && ($changedPropertyValue instanceof ImageInterface || $changedPropertyValue === null)
            ) {
                $contentChanges[$propertyName] = [
                    'type' => 'image',
                    'propertyLabel' => $this->getPropertyLabel($propertyName, $changedNode),
                    'original' => $originalPropertyValue,
                    'changed' => $changedPropertyValue
                ];
            } elseif ($originalPropertyValue instanceof AssetInterface
                || $changedPropertyValue instanceof AssetInterface) {
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
     * @param NodeInterface $changedNode
     * @return string
     */
    protected function getPropertyLabel($propertyName, NodeInterface $changedNode)
    {
        $properties = $changedNode->getNodeType()->getProperties();
        if (!isset($properties[$propertyName]) ||
            !isset($properties[$propertyName]['ui']['label'])
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
     *
     * @param Workspace $excludedWorkspace If set, this workspace will be excluded from the list of returned workspaces
     * @return array<string,string>
     */
    protected function prepareBaseWorkspaceOptions(Workspace $excludedWorkspace = null): array
    {
        $baseWorkspaceOptions = [];
        foreach ($this->workspaceFinder->findAll() as $workspace) {
            /** @var Workspace $workspace */
            if (!$workspace->isPersonalWorkspace()
                && $workspace !== $excludedWorkspace
                && ($workspace->isPublicWorkspace()
                    || $workspace->isInternalWorkspace()
                    || $this->userService->currentUserCanManageWorkspace($workspace))
            ) {
                $baseWorkspaceOptions[(string)$workspace->getWorkspaceName()] = (string)$workspace->getWorkspaceTitle();
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
        foreach ($this->userService->getUsers() as $user) {
            /** @var User $user */
            $ownerOptions[$this->persistenceManager->getIdentifierByObject($user)] = $user->getLabel();
        }

        return $ownerOptions;
    }

    private function getCurrentUserIdentifier(): UserIdentifier
    {
        return UserIdentifier::fromString(
            $this->persistenceManager->getIdentifierByObject($this->userService->getCurrentUser())
        );
    }

    private function getBaseWorkspaceWhenSureItExists(Workspace $workspace): Workspace
    {
        /** @var WorkspaceName $baseWorkspaceName We expect this to exist */
        $baseWorkspaceName = $workspace->getBaseWorkspaceName();
        /** @var Workspace $baseWorkspace We expect this to exist */
        $baseWorkspace = $this->workspaceFinder->findOneByName($baseWorkspaceName);

        return $baseWorkspace;
    }
}
