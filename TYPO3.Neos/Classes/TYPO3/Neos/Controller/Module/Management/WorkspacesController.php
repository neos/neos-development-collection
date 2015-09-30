<?php
namespace TYPO3\Neos\Controller\Module\Management;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Error\Message;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Property\PropertyMappingConfigurationBuilder;
use TYPO3\Flow\Security\Context;
use TYPO3\Neos\Controller\Module\AbstractModuleController;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Domain\Service\ContentContextFactory;
use TYPO3\Neos\Service\PublishingService;
use TYPO3\Neos\Service\UserService;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;

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
        $userWorkspace = $this->userService->getUserWorkspace();
        $workspacesAndCounts = array(
            $userWorkspace->getName() => array(
                'workspace' => $userWorkspace,
                'changesCounts' => $this->computeChangesCount($userWorkspace)
            )
        );

        foreach ($this->workspaceRepository->findAll() as $workspace) {
            /** @var Workspace $workspace */
            if (substr($workspace->getName(), 0, 5) !== 'user-' && $workspace->getBaseWorkspace() !== null) {
                $workspacesAndCounts[$workspace->getName()]['workspace'] = $workspace;
                $workspacesAndCounts[$workspace->getName()]['changesCounts'] = $this->computeChangesCount($workspace);
                $workspacesAndCounts[$workspace->getName()]['dependentWorkspacesCount'] = count($this->workspaceRepository->findByBaseWorkspace($workspace));
            }
        }

        $this->view->assign('selectedWorkspace', $userWorkspace);
        $this->view->assign('workspacesAndChangeCounts', $workspacesAndCounts);
    }

    /**
     * @param Workspace $workspace
     * @return void
     */
    public function showAction(Workspace $workspace)
    {
        $this->view->assignMultiple(array(
            'selectedWorkspace' => $workspace,
            'selectedWorkspaceLabel' => $workspace->getTitle() ?: $workspace->getName(),
            'baseWorkspaceName' => $workspace->getBaseWorkspace()->getName(),
            'baseWorkspaceLabel' => $workspace->getBaseWorkspace()->getTitle() ?: $workspace->getBaseWorkspace()->getName(),
            'siteChanges' => $this->computeSiteChanges($workspace)
        ));
    }

    /**
     * @return void
     */
    public function newAction()
    {
    }

    /**
     * Create a workspace
     *
     * @Flow\Validate(argumentName="name", type="\TYPO3\Flow\Validation\Validator\NotEmptyValidator")
     * @param string $name Name of the workspace, for example "christmas-campaign"
     * @param string $title Human friendly title of the workspace, for example "Christmas Campaign"
     * @param string $description A description explaining the purpose of the new workspace
     * @param string $owner The identifier of a User to own the workspace
     * @return void
     */
    public function createAction($name, $title = '', $description = '')
    {
        if (substr($name, 0, 5) === 'user-') {
            $this->addFlashMessage('The workspace "%s" was not created. User workspaces cannot be created manually.', 'Not created', Message::SEVERITY_WARNING, array($name), 1437728449);
            $this->redirect('new');
        }

        $workspace = $this->workspaceRepository->findOneByName($name);
        if ($workspace instanceof Workspace) {
            $this->addFlashMessage('The workspace "%s" already exists.', 'Workspace exists', Message::SEVERITY_WARNING, array($name), 1437563199);
            $this->redirect('new');
        }

        $baseWorkspace = $this->workspaceRepository->findOneByName('live');
        $owner = $this->userService->getBackendUser();

        $workspace = new Workspace($name, $baseWorkspace, $owner);

        if ($title === '') {
            $title = $name;
        }
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
        $this->addFlashMessage('The workspace "%s" has been updated.', 'Update', null, array($workspace->getName()), 1437723235);
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
            $this->addFlashMessage('Did not delete workspace "%s" because it is a user workspace. User workspaces cannot be deleted manually.', 'Not removed', Message::SEVERITY_WARNING, array($workspace->getName()), 1437725696);
            $this->redirect('index');
        }

        $dependentWorkspaces = $this->workspaceRepository->findByBaseWorkspace($workspace);
        if (count($dependentWorkspaces) > 0) {
            $dependentWorkspaceNames = array();
            foreach ($dependentWorkspaces as $dependentWorkspace) {
                $dependentWorkspaceNames[] = $dependentWorkspace->getName();
            }

            $this->addFlashMessage('Workspace "%s" cannot be deleted because the following workspaces are based on it: %s', 'Not removed', Message::SEVERITY_WARNING, array($workspace->getName(), implode(', ', $dependentWorkspaceNames)), 1437725699);
            $this->redirect('index');
        }

        $nodesCount = 0;
        try {
            $nodesCount = $this->publishingService->getUnpublishedNodesCount($workspace);
        } catch (\Exception $exception) {
            $this->addFlashMessage('An error occurred while fetching unpublished nodes from workspace %s, nothing was deleted.', 'Not removed', Message::SEVERITY_WARNING, array($workspace->getName()), 1437725705);
            $this->redirect('index');
        }
        if ($nodesCount > 0) {
            $this->addFlashMessage('Did not delete workspace "%s" because it contains %s unpublished node(s).', 'Not removed', Message::SEVERITY_WARNING, array($workspace->getName(), $nodesCount), 1437725703);
            $this->redirect('index');
        }

        $this->workspaceRepository->remove($workspace);
        $this->addFlashMessage('The workspace "%s" has been removed.', 'Removal', null, array($workspace->getName()), 1437725708);
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
        $userWorkspace = $this->userService->getUserWorkspace();

        if ($this->publishingService->getUnpublishedNodesCount($userWorkspace) > 0) {
            $this->addFlashMessage('Your user workspace contains changes, please publish or discard them first.', 'No editing possible', Message::SEVERITY_WARNING, array(), 1437833387);
            $this->redirect('show', null, null, array('workspace' => $targetWorkspace));
        }

        $userWorkspace->setBaseWorkspace($targetWorkspace);
        $this->workspaceRepository->update($userWorkspace);

        $contextProperties = $targetNode->getContext()->getProperties();
        $contextProperties['workspaceName'] = $userWorkspace->getName();
        $context = $this->contextFactory->create($contextProperties);

        $mainRequest = $this->controllerContext->getRequest()->getMainRequest();
        $this->uriBuilder->setRequest($mainRequest);
        $this->redirect('show', 'Frontend\\Node', 'TYPO3.Neos', array('node' => $context->getNode($targetNode->getPath())));
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
        $this->addFlashMessage('Node has been published', 'Node published', null, array(), 1412421581);
        $this->redirect('show', null, null, array('workspace' => $selectedWorkspace));
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
        $this->addFlashMessage('Node has been discarded', 'Node discarded', null, array(), 1412420292);
        $this->redirect('show', null, null, array('workspace' => $selectedWorkspace));
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
                $this->addFlashMessage('Selected changes have been published', null, null, array(), 412420736);
            break;
            case 'discard':
                $this->publishingService->discardNodes($nodes);
                $this->addFlashMessage('Selected changes have been discarded', null, null, array(), 412420851);
            break;
            default:
                throw new \RuntimeException('Invalid action "' . $action . '" given.', 1346167441);
        }

        $this->redirect('show', null, null, array('workspace' => $selectedWorkspace));
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
        $this->addFlashMessage('Changes in workspace "%s" have been published to "%s"', 'Changes published', Message::SEVERITY_OK, array($workspace->getName(), $targetWorkspace->getName()), 1412420808);
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
        $this->addFlashMessage('Changes in workspace "%s" have been discarded', 'Changes discarded', Message::SEVERITY_OK, array($workspace->getName()), 1412420835);
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
        $changesCount = array('new' => 0, 'changed' => 0, 'removed' => 0, 'total' => 0);
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
        $siteChanges = array();
        foreach ($this->publishingService->getUnpublishedNodes($selectedWorkspace) as $node) {
            if (!$node->getNodeType()->isOfType('TYPO3.Neos:ContentCollection')) {
                $pathParts = explode('/', $node->getPath());
                if (count($pathParts) > 2) {
                    $siteNodeName = $pathParts[2];
                    $q = new FlowQuery(array($node));
                    $document = $q->closest('[instanceof TYPO3.Neos:Document]')->get(0);
                    // FIXME: $document will be NULL if we have a broken root line for this node. This actually should never happen, but currently can in some scenarios.
                    if ($document !== null) {
                        $documentPath = implode('/', array_slice(explode('/', $document->getPath()), 3));
                        $relativePath = str_replace(sprintf('/sites/%s/%s', $siteNodeName, $documentPath), '', $node->getPath());
                        if (!isset($siteChanges[$siteNodeName]['siteNode'])) {
                            $siteChanges[$siteNodeName]['siteNode'] = $this->siteRepository->findOneByNodeName($siteNodeName);
                        }
                        $siteChanges[$siteNodeName]['documents'][$documentPath]['documentNode'] = $document;
                        $change = array('node' => $node);
                        if ($node->getNodeType()->isOfType('TYPO3.Neos:Node')) {
                            $change['configuration'] = $node->getNodeType()->getFullConfiguration();
                        }
                        $siteChanges[$siteNodeName]['documents'][$documentPath]['changes'][$relativePath] = $change;
                    }
                }
            }
        }

        $liveContext = $this->contextFactory->create(array(
            'workspaceName' => 'live'
        ));

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
}
