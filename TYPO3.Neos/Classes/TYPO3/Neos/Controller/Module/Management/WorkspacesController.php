<?php
namespace TYPO3\Neos\Controller\Module\Management;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Message;
use TYPO3\Flow\I18n\Translator;
use TYPO3\Flow\Mvc\ActionRequest;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Property\PropertyMappingConfigurationBuilder;
use TYPO3\Flow\Security\Context;
use TYPO3\Neos\Controller\Module\AbstractModuleController;
use TYPO3\Neos\Domain\Model\User;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Domain\Service\ContentContextFactory;
use TYPO3\Neos\Domain\Service\UserService;
use TYPO3\Neos\Domain\Service\SiteService;
use TYPO3\Neos\Service\PublishingService;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\Utility;

/**
 * The Neos Workspaces module controller
 *
 * @Flow\Scope("singleton")
 */
class WorkspacesController extends AbstractModuleController
{

    /**
     * @Flow\Inject
     * @var PublishingService
     */
    protected $publishingService;

    /**
     * @Flow\Inject
     * @var WorkspaceRepository
     */
    protected $workspaceRepository;

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
     * @var ContentContextFactory
     */
    protected $contextFactory;

    /**
     * @Flow\Inject
     * @var PropertyMappingConfigurationBuilder
     */
    protected $propertyMappingConfigurationBuilder;

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
     * @return void
     */
    protected function initializeAction()
    {
        if ($this->arguments->hasArgument('node')) {
            $this->arguments->getArgument('node')->getPropertyMappingConfiguration()->setTypeConverterOption('TYPO3\TYPO3CR\TypeConverter\NodeConverter', \TYPO3\TYPO3CR\TypeConverter\NodeConverter::REMOVED_CONTENT_SHOWN, true);
        }
        if ($this->arguments->hasArgument('nodes')) {
            $this->arguments->getArgument('nodes')->getPropertyMappingConfiguration()->forProperty('*')->setTypeConverterOption('TYPO3\TYPO3CR\TypeConverter\NodeConverter', \TYPO3\TYPO3CR\TypeConverter\NodeConverter::REMOVED_CONTENT_SHOWN, true);
        }
        parent::initializeAction();
    }

    /**
     * Display a list of unpublished content
     *
     * @return void
     */
    public function indexAction()
    {
        $currentAccount = $this->securityContext->getAccount();
        $userWorkspace = $this->workspaceRepository->findOneByName('user-' . $currentAccount->getAccountIdentifier());
        /** @var Workspace $userWorkspace */

        $workspacesAndCounts = [
            $userWorkspace->getName() => [
                'workspace' => $userWorkspace,
                'changesCounts' => $this->computeChangesCount($userWorkspace),
                'canPublish' => false,
                'canManage' => false,
                'canDelete' => false
            ]
        ];

        foreach ($this->workspaceRepository->findAll() as $workspace) {
            /** @var Workspace $workspace */
            // FIXME: This check should be implemented through a specialized Workspace Privilege or something similar
            if (!$workspace->isPersonalWorkspace() && ($workspace->isInternalWorkspace() || $this->userService->currentUserCanManageWorkspace($workspace))) {
                $workspacesAndCounts[$workspace->getName()]['workspace'] = $workspace;
                $workspacesAndCounts[$workspace->getName()]['changesCounts'] = $this->computeChangesCount($workspace);
                $workspacesAndCounts[$workspace->getName()]['canPublish'] = $this->userService->currentUserCanPublishToWorkspace($workspace);
                $workspacesAndCounts[$workspace->getName()]['canManage'] = $this->userService->currentUserCanManageWorkspace($workspace);
                $workspacesAndCounts[$workspace->getName()]['dependentWorkspacesCount'] = count($this->workspaceRepository->findByBaseWorkspace($workspace));
            }
        }

        $this->view->assign('userWorkspace', $userWorkspace);
        $this->view->assign('workspacesAndChangeCounts', $workspacesAndCounts);
    }

    /**
     * @param Workspace $workspace
     * @return void
     */
    public function showAction(Workspace $workspace)
    {
        $this->view->assignMultiple([
            'selectedWorkspace' => $workspace,
            'selectedWorkspaceLabel' => $workspace->getTitle() ?: $workspace->getName(),
            'baseWorkspaceName' => $workspace->getBaseWorkspace()->getName(),
            'baseWorkspaceLabel' => $workspace->getBaseWorkspace()->getTitle() ?: $workspace->getBaseWorkspace()->getName(),
            'canPublishToBaseWorkspace' => $this->userService->currentUserCanPublishToWorkspace($workspace->getBaseWorkspace()),
            'siteChanges' => $this->computeSiteChanges($workspace)
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
     * @Flow\Validate(argumentName="title", type="\TYPO3\Flow\Validation\Validator\NotEmptyValidator")
     * @param string $title Human friendly title of the workspace, for example "Christmas Campaign"
     * @param Workspace $baseWorkspace Workspace the new workspace should be based on
     * @param string $visibility Visibility of the new workspace, must be either "internal" or "shared"
     * @param string $description A description explaining the purpose of the new workspace
     * @return void
     */
    public function createAction($title, Workspace $baseWorkspace, $visibility, $description = '')
    {
        $workspace = $this->workspaceRepository->findOneByTitle($title);
        if ($workspace instanceof Workspace) {
            $this->addFlashMessage($this->translator->translateById('workspaces.workspaceWithThisTitleAlreadyExists', [], null, null, 'Modules', 'TYPO3.Neos'), '', Message::SEVERITY_WARNING);
            $this->redirect('new');
        }

        $workspaceName = Utility::renderValidNodeName($title) . '-' . substr(uniqid(), 0, 4);
        while ($this->workspaceRepository->findOneByName($workspaceName) instanceof Workspace) {
            $workspaceName = Utility::renderValidNodeName($title) . '-' . substr(uniqid(), 0, 4);
        }

        if ($visibility === 'private') {
            $owner = $this->userService->getCurrentUser();
        } else {
            $owner = null;
        }

        $workspace = new Workspace($workspaceName, $baseWorkspace, $owner);
        $workspace->setTitle($title);
        $workspace->setDescription($description);

        $this->workspaceRepository->add($workspace);
        $this->redirect('index');
    }

    /**
     * Edit a workspace
     *
     * @param Workspace $workspace
     * @return void
     */
    public function editAction(Workspace $workspace)
    {
        $this->view->assign('workspace', $workspace);
        $this->view->assign('baseWorkspaceOptions', $this->prepareBaseWorkspaceOptions($workspace));
        $this->view->assign('disableBaseWorkspaceSelector', $this->publishingService->getUnpublishedNodesCount($workspace) > 0);
        $this->view->assign('showOwnerSelector', $this->userService->currentUserCanTransferOwnershipOfWorkspace($workspace));
        $this->view->assign('ownerOptions', $this->prepareOwnerOptions());
    }

    /**
     * Update a workspace
     *
     * @param Workspace $workspace A workspace to update
     * @return void
     */
    public function updateAction(Workspace $workspace)
    {
        if ($workspace->getTitle() === '') {
            $workspace->setTitle($workspace->getName());
        }

        $this->workspaceRepository->update($workspace);
        $this->addFlashMessage($this->translator->translateById('workspaces.workspaceHasBeenUpdated', [$workspace->getTitle()], null, null, 'Modules', 'TYPO3.Neos'));
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
        if (substr($workspace->getName(), 0, 5) === 'user-') {
            $this->redirect('index');
        }

        $dependentWorkspaces = $this->workspaceRepository->findByBaseWorkspace($workspace);
        if (count($dependentWorkspaces) > 0) {
            $dependentWorkspaceTitles = [];
            /** @var Workspace $dependentWorkspace */
            foreach ($dependentWorkspaces as $dependentWorkspace) {
                $dependentWorkspaceTitles[] = $dependentWorkspace->getTitle();
            }

            $message = $this->translator->translateById('workspaces.workspaceCannotBeDeletedBecauseOfDependencies', [$workspace->getTitle(), implode(', ', $dependentWorkspaceTitles)], null, null, 'Modules', 'TYPO3.Neos');
            $this->addFlashMessage($message, '', Message::SEVERITY_WARNING);
            $this->redirect('index');
        }

        $nodesCount = 0;
        try {
            $nodesCount = $this->publishingService->getUnpublishedNodesCount($workspace);
        } catch (\Exception $exception) {
            $message = $this->translator->translateById('workspaces.notDeletedErrorWhileFetchingUnpublishedNodes', [$workspace->getTitle()], null, null, 'Modules', 'TYPO3.Neos');
            $this->addFlashMessage($message, '', Message::SEVERITY_WARNING);
            $this->redirect('index');
        }
        if ($nodesCount > 0) {
            $message = $this->translator->translateById('workspaces.workspaceCannotBeDeletedBecauseOfUnpublishedNodes', [$workspace->getTitle(), $nodesCount], $nodesCount, null, 'Modules', 'TYPO3.Neos');
            $this->addFlashMessage($message, '', Message::SEVERITY_WARNING);
            $this->redirect('index');
        }

        $this->workspaceRepository->remove($workspace);
        $this->addFlashMessage($message = $this->translator->translateById('workspaces.workspaceHasBeenRemoved', [$workspace->getTitle()], null, null, 'Modules', 'TYPO3.Neos'));
        $this->redirect('index');
    }

    /**
     * Rebase the current users personal workspace onto the given $targetWorkspace and then
     * redirects to the $targetNode in the content module.
     *
     * @param NodeInterface $targetNode
     * @param Workspace $targetWorkspace
     * @return void
     */
    public function rebaseAndRedirectAction(NodeInterface $targetNode, Workspace $targetWorkspace)
    {
        $currentAccount = $this->securityContext->getAccount();
        $personalWorkspace = $this->workspaceRepository->$this->findOneByName('user-' . $currentAccount->getAccountIdentifier());
        /** @var Workspace $personalWorkspace */

        if ($this->publishingService->getUnpublishedNodesCount($personalWorkspace) > 0) {
            $message = $this->translator->translateById('workspaces.cantEditBecauseWorkspaceContainsChanges', [], null, null, 'Modules', 'TYPO3.Neos');
            $this->addFlashMessage($message, '', Message::SEVERITY_WARNING, [], 1437833387);
            $this->redirect('show', null, null, ['workspace' => $targetWorkspace]);
        }

        $personalWorkspace->setBaseWorkspace($targetWorkspace);
        $this->workspaceRepository->update($personalWorkspace);

        $contextProperties = $targetNode->getContext()->getProperties();
        $contextProperties['workspaceName'] = $personalWorkspace->getName();
        $context = $this->contextFactory->create($contextProperties);

        $mainRequest = $this->controllerContext->getRequest()->getMainRequest();
        /** @var ActionRequest $mainRequest */
        $this->uriBuilder->setRequest($mainRequest);
        $this->redirect('show', 'Frontend\\Node', 'TYPO3.Neos', ['node' => $context->getNode($targetNode->getPath())]);
    }

    /**
     * Publish a single node
     *
     * @param NodeInterface $node
     * @param Workspace $selectedWorkspace
     */
    public function publishNodeAction(NodeInterface $node, Workspace $selectedWorkspace)
    {
        $this->publishingService->publishNode($node);
        $this->addFlashMessage($this->translator->translateById('workspaces.selectedChangeHasBeenPublished', [], null, null, 'Modules', 'TYPO3.Neos'));
        $this->redirect('show', null, null, ['workspace' => $selectedWorkspace]);
    }

    /**
     * Discard a a single node
     *
     * @param NodeInterface $node
     * @param Workspace $selectedWorkspace
     * @throws \TYPO3\TYPO3CR\Exception\WorkspaceException
     */
    public function discardNodeAction(NodeInterface $node, Workspace $selectedWorkspace)
    {
        // Hint: we cannot use $node->remove() here, as this removes the node recursively (but we just want to *discard changes*)
        $this->publishingService->discardNode($node);
        $this->addFlashMessage($this->translator->translateById('workspaces.selectedChangeHasBeenDiscarded', [], null, null, 'Modules', 'TYPO3.Neos'));
        $this->redirect('show', null, null, ['workspace' => $selectedWorkspace]);
    }

    /**
     * Publishes or discards the given nodes
     *
     * @param array $nodes <\TYPO3\TYPO3CR\Domain\Model\NodeInterface> $nodes
     * @param string $action
     * @param Workspace $selectedWorkspace
     * @throws \Exception
     * @throws \TYPO3\Flow\Property\Exception
     * @throws \TYPO3\Flow\Security\Exception
     */
    public function publishOrDiscardNodesAction(array $nodes, $action, Workspace $selectedWorkspace = null)
    {
        $propertyMappingConfiguration = $this->propertyMappingConfigurationBuilder->build();
        $propertyMappingConfiguration->setTypeConverterOption('TYPO3\TYPO3CR\TypeConverter\NodeConverter', \TYPO3\TYPO3CR\TypeConverter\NodeConverter::REMOVED_CONTENT_SHOWN, true);
        foreach ($nodes as $key => $node) {
            $nodes[$key] = $this->propertyMapper->convert($node, 'TYPO3\TYPO3CR\Domain\Model\NodeInterface', $propertyMappingConfiguration);
        }
        switch ($action) {
            case 'publish':
                foreach ($nodes as $node) {
                    $this->publishingService->publishNode($node);
                }
                $this->addFlashMessage($this->translator->translateById('workspaces.selectedChangesHaveBeenPublished', [], null, null, 'Modules', 'TYPO3.Neos'));
            break;
            case 'discard':
                $this->publishingService->discardNodes($nodes);
                $this->addFlashMessage($this->translator->translateById('workspaces.selectedChangesHaveBeenDiscarded', [], null, null, 'Modules', 'TYPO3.Neos'));
            break;
            default:
                throw new \RuntimeException('Invalid action "' . $action . '" given.', 1346167441);
        }

        $this->redirect('show', null, null, ['workspace' => $selectedWorkspace]);
    }

    /**
     * Publishes the whole workspace
     *
     * @param Workspace $workspace
     * @return void
     */
    public function publishWorkspaceAction(Workspace $workspace)
    {
        if (($targetWorkspace = $workspace->getBaseWorkspace()) === null) {
            $targetWorkspace = $this->workspaceRepository->findOneByName('live');
        }
        $workspace->publish($targetWorkspace);
        $this->addFlashMessage($this->translator->translateById('workspaces.allChangesInWorkspaceHaveBeenPublished', [$workspace->getTitle(), $targetWorkspace->getTitle()], null, null, 'Modules', 'TYPO3.Neos'));
        $this->redirect('index');
    }

    /**
     * Discards content of the whole workspace
     *
     * @param Workspace $workspace
     * @return void
     */
    public function discardWorkspaceAction(Workspace $workspace)
    {
        $unpublishedNodes = $this->publishingService->getUnpublishedNodes($workspace);
        $this->publishingService->discardNodes($unpublishedNodes);
        $this->addFlashMessage($this->translator->translateById('workspaces.allChangesInWorkspaceHaveBeenDiscarded', [$workspace->getTitle()], null, null, 'Modules', 'TYPO3.Neos'));
        $this->redirect('index');
    }

    /**
     * Computes the number of added, changed and removed nodes for the given workspace
     *
     * @param Workspace $selectedWorkspace
     * @return array
     */
    protected function computeChangesCount(Workspace $selectedWorkspace)
    {
        $changesCount = ['new' => 0, 'changed' => 0, 'removed' => 0, 'total' => 0];
        foreach ($this->computeSiteChanges($selectedWorkspace) as $siteChanges) {
            foreach ($siteChanges['documents'] as $documentChanges) {
                foreach ($documentChanges['changes'] as $change) {
                    if ($change['node']->isRemoved()) {
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
     *
     * @param Workspace $selectedWorkspace
     * @return array
     */
    protected function computeSiteChanges(Workspace $selectedWorkspace)
    {
        $siteChanges = [];
        foreach ($this->publishingService->getUnpublishedNodes($selectedWorkspace) as $node) {
            /** @var NodeInterface $node */
            if (!$node->getNodeType()->isOfType('TYPO3.Neos:ContentCollection')) {
                $pathParts = explode('/', $node->getPath());
                if (count($pathParts) > 2) {
                    $siteNodeName = $pathParts[2];
                    $q = new FlowQuery([$node]);
                    $document = $q->closest('[instanceof TYPO3.Neos:Document]')->get(0);
                    // FIXME: $document will be null if we have a broken root line for this node. This actually should never happen, but currently can in some scenarios.
                    if ($document !== null) {
                        $documentPath = implode('/', array_slice(explode('/', $document->getPath()), 3));
                        $relativePath = str_replace(sprintf(SiteService::SITES_ROOT_PATH . '/%s/%s', $siteNodeName, $documentPath), '', $node->getPath());
                        if (!isset($siteChanges[$siteNodeName]['siteNode'])) {
                            $siteChanges[$siteNodeName]['siteNode'] = $this->siteRepository->findOneByNodeName($siteNodeName);
                        }
                        $siteChanges[$siteNodeName]['documents'][$documentPath]['documentNode'] = $document;
                        $change = ['node' => $node];
                        if ($node->getNodeType()->isOfType('TYPO3.Neos:Node')) {
                            $change['configuration'] = $node->getNodeType()->getFullConfiguration();
                        }
                        $siteChanges[$siteNodeName]['documents'][$documentPath]['changes'][$relativePath] = $change;
                    }
                }
            }
        }

        $liveContext = $this->contextFactory->create([
            'workspaceName' => 'live'
        ]);

        ksort($siteChanges);
        foreach ($siteChanges as $siteKey => $site) {
            foreach ($site['documents'] as $documentKey => $document) {
                foreach ($document['changes'] as $changeKey => $change) {
                    $liveNode = $liveContext->getNodeByIdentifier($change['node']->getIdentifier());
                    $siteChanges[$siteKey]['documents'][$documentKey]['changes'][$changeKey]['isNew'] = is_null($liveNode);
                    $siteChanges[$siteKey]['documents'][$documentKey]['changes'][$changeKey]['isMoved'] = $liveNode && $change['node']->getPath() !== $liveNode->getPath();
                }
            }
            ksort($siteChanges[$siteKey]['documents']);
        }

        return $siteChanges;
    }

    /**
     * Creates an array of workspace names and their respective titles which are possible base workspaces for other
     * workspaces.
     *
     * @param Workspace $excludedWorkspace If set, this workspace will be excluded from the list of returned workspaces
     * @return array
     */
    protected function prepareBaseWorkspaceOptions(Workspace $excludedWorkspace = null)
    {
        $baseWorkspaceOptions = [];
        foreach ($this->workspaceRepository->findAll() as $workspace) {
            /** @var Workspace $workspace */
            if (!$workspace->isPersonalWorkspace() && $workspace !== $excludedWorkspace) {
                $baseWorkspaceOptions[$workspace->getName()] = $workspace->getTitle();
            }
        }

        return $baseWorkspaceOptions;
    }

    /**
     * Creates an array of user names and their respective labels which are possible owners for a workspace.
     *
     * @return array
     */
    protected function prepareOwnerOptions()
    {
        $ownerOptions = ['' => '-'];
        foreach ($this->userService->getUsers() as $user) {
            /** @var User $user */
            $ownerOptions[$this->persistenceManager->getIdentifierByObject($user)] = $user->getLabel();
        }

        return $ownerOptions;
    }
}
