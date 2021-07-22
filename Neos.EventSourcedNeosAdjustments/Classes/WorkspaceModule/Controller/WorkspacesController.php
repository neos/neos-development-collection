<?php
declare(strict_types=1);

namespace Neos\EventSourcedNeosAdjustments\WorkspaceModule\Controller;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\Diff\Diff;
use Neos\Diff\Renderer\Html\HtmlArrayRenderer;
use Neos\Eel\FlowQuery\FlowQuery;
use Neos\EventSourcedContentRepository\Domain\Context\Parameters\VisibilityConstraints;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\CreateWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\DiscardWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command\PublishWorkspace;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\WorkspaceCommandHandler;
use Neos\EventSourcedContentRepository\Domain\Projection\Changes\ChangeFinder;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\ContentGraphInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Content\NodeInterface;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\Workspace;
use Neos\EventSourcedContentRepository\Domain\Projection\Workspace\WorkspaceFinder;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceDescription;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceTitle;
use Neos\EventSourcedNeosAdjustments\Domain\Context\Workspace\WorkspaceName as NeosWorkspaceName;
use Neos\EventSourcedNeosAdjustments\WorkspaceModule\WorkspaceUserService;
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
use Neos\Neos\Domain\Service\ContentDimensionPresetSourceInterface;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\Service\PublishingService;
use Neos\ContentRepository\Exception\WorkspaceException;
use Neos\ContentRepository\TypeConverter\NodeConverter;
use Neos\ContentRepository\Utility;
use Neos\Neos\Utility\User as UserUtility;

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
     * @var WorkspaceUserService
     */
    protected $workspaceUserService;

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
     * TODO REMOVE
     * @var ContentDimensionPresetSourceInterface
     * @Flow\Inject
     */
    protected $contentDimensionPresetSource;

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
     * @return void
     */
    /*protected function initializeAction()
    {
        if ($this->arguments->hasArgument('node')) {
            $this->arguments->getArgument('node')->getPropertyMappingConfiguration()->setTypeConverterOption(NodeConverter::class, NodeConverter::REMOVED_CONTENT_SHOWN, true);
        }
        if ($this->arguments->hasArgument('nodes')) {
            $this->arguments->getArgument('nodes')->getPropertyMappingConfiguration()->forProperty('*')->setTypeConverterOption(NodeConverter::class, NodeConverter::REMOVED_CONTENT_SHOWN, true);
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
        $userWorkspace = $this->workspaceFinder->findOneByName(NeosWorkspaceName::fromAccountIdentifier($currentAccount->getAccountIdentifier())->toContentRepositoryWorkspaceName());

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
            /** @var Workspace $workspace */
            // FIXME: This check should be implemented through a specialized Workspace Privilege or something similar
            // TODO $this->userService->currentUserCanManageWorkspace($workspace)
            if (!$workspace->isPersonalWorkspace() && ($workspace->isInternalWorkspace())) {
                $workspaceName = (string)$workspace->getWorkspaceName();
                $workspacesAndCounts[$workspaceName]['workspace'] = $workspace;
                $workspacesAndCounts[$workspaceName]['changesCounts'] = $this->computeChangesCount($workspace);
                $workspacesAndCounts[$workspaceName]['canPublish'] = $this->workspaceUserService->currentUserCanPublishToWorkspace($workspace);
                $workspacesAndCounts[$workspaceName]['canManage'] = $this->workspaceUserService->currentUserCanManageWorkspace($workspace);
                $workspacesAndCounts[$workspaceName]['dependentWorkspacesCount'] = count($this->workspaceFinder->findByBaseWorkspace($workspace->getWorkspaceName()));
            }
        }

        $this->view->assign('userWorkspace', $userWorkspace);
        $this->view->assign('workspacesAndChangeCounts', $workspacesAndCounts);
    }


    public function showAction(WorkspaceName $workspace)
    {
        $workspace = $this->workspaceFinder->findOneByName($workspace);
        $this->view->assignMultiple([
            'selectedWorkspace' => $workspace,
            'selectedWorkspaceLabel' => $workspace->workspaceTitle ?: $workspace->getWorkspaceName(),
            'baseWorkspaceName' => $workspace->getBaseWorkspaceName(),
            'baseWorkspaceLabel' => $workspace->getBaseWorkspaceName(), // TODO fallback to title
            'canPublishToBaseWorkspace' => true, // TODO $this->userService->currentUserCanPublishToWorkspace($workspace->getBaseWorkspace()),
            'siteChanges' => $this->computeSiteChanges($workspace),
            'contentDimensions' => $this->contentDimensionPresetSource->getAllPresets()
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
    public function createAction(WorkspaceTitle $title, WorkspaceName $baseWorkspace, $visibility, WorkspaceDescription $description = null)
    {
        // TODO
        //$workspace = $this->workspaceFinder->findOneByWorkspaceTitle($title);
        //if ($workspace instanceof Workspace) {
        //    $this->addFlashMessage($this->translator->translateById('workspaces.workspaceWithThisTitleAlreadyExists', [], null, null, 'Modules', 'Neos.Neos'), '', Message::SEVERITY_WARNING);
        //    $this->redirect('new');
        //}

        $workspaceName = new WorkspaceName(Utility::renderValidNodeName($title) . '-' . substr(base_convert(microtime(false), 10, 36), -5, 5));
        while ($this->workspaceFinder->findOneByName($workspaceName) instanceof Workspace) {
            $workspaceName = Utility::renderValidNodeName($title) . '-' . substr(base_convert(microtime(false), 10, 36), -5, 5);
        }

        if ($visibility === 'private') {
            $owner = UserIdentifier::fromString($this->persistenceManager->getIdentifierByObject($this->userService->getCurrentUser()));
        } else {
            $owner = null;
        }

        $command = new CreateWorkspace(
            $workspaceName,
            $baseWorkspace,
            $title,
            $description,
            UserIdentifier::fromString($this->persistenceManager->getIdentifierByObject($this->userService->getCurrentUser())),
            ContentStreamIdentifier::create(),
            $owner
        );
        $this->workspaceCommandHandler->handleCreateWorkspace($command)->blockUntilProjectionsAreUpToDate();

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
        $this->view->assign('workspace', $workspace);
        $this->view->assign('baseWorkspaceOptions', $this->prepareBaseWorkspaceOptions($workspace));
        // TODO: $this->view->assign('disableBaseWorkspaceSelector', $this->publishingService->getUnpublishedNodesCount($workspace) > 0);
        $this->view->assign('showOwnerSelector', $this->workspaceUserService->currentUserCanTransferOwnershipOfWorkspace($workspace));
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
            ->setTypeConverterOption(PersistentObjectConverter::class, PersistentObjectConverter::CONFIGURATION_TARGET_TYPE, User::class);
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
        if ($workspace->getTitle() === '') {
            $workspace->setTitle($workspace->getName());
        }

        $this->workspaceFinder->update($workspace);
        $this->addFlashMessage($this->translator->translateById('workspaces.workspaceHasBeenUpdated', [$workspace->getTitle()], null, null, 'Modules', 'Neos.Neos'));
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

        $dependentWorkspaces = $this->workspaceFinder->findByBaseWorkspace($workspace);
        if (count($dependentWorkspaces) > 0) {
            $dependentWorkspaceTitles = [];
            /** @var Workspace $dependentWorkspace */
            foreach ($dependentWorkspaces as $dependentWorkspace) {
                $dependentWorkspaceTitles[] = $dependentWorkspace->getTitle();
            }

            $message = $this->translator->translateById('workspaces.workspaceCannotBeDeletedBecauseOfDependencies', [$workspace->getTitle(), implode(', ', $dependentWorkspaceTitles)], null, null, 'Modules', 'Neos.Neos');
            $this->addFlashMessage($message, '', Message::SEVERITY_WARNING);
            $this->redirect('index');
        }

        $nodesCount = 0;
        try {
            $nodesCount = $this->publishingService->getUnpublishedNodesCount($workspace);
        } catch (\Exception $exception) {
            $message = $this->translator->translateById('workspaces.notDeletedErrorWhileFetchingUnpublishedNodes', [$workspace->getTitle()], null, null, 'Modules', 'Neos.Neos');
            $this->addFlashMessage($message, '', Message::SEVERITY_WARNING);
            $this->redirect('index');
        }
        if ($nodesCount > 0) {
            $message = $this->translator->translateById('workspaces.workspaceCannotBeDeletedBecauseOfUnpublishedNodes', [$workspace->getTitle(), $nodesCount], $nodesCount, null, 'Modules', 'Neos.Neos');
            $this->addFlashMessage($message, '', Message::SEVERITY_WARNING);
            $this->redirect('index');
        }

        $this->workspaceFinder->remove($workspace);
        $this->addFlashMessage($message = $this->translator->translateById('workspaces.workspaceHasBeenRemoved', [$workspace->getTitle()], null, null, 'Modules', 'Neos.Neos'));
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
        $personalWorkspace = $this->workspaceFinder->findOneByName(UserUtility::getPersonalWorkspaceNameForUsername($currentAccount->getAccountIdentifier()));
        /** @var Workspace $personalWorkspace */

        if ($personalWorkspace !== $targetWorkspace) {
            if ($this->publishingService->getUnpublishedNodesCount($personalWorkspace) > 0) {
                $message = $this->translator->translateById('workspaces.cantEditBecauseWorkspaceContainsChanges', [], null, null, 'Modules', 'Neos.Neos');
                $this->addFlashMessage($message, '', Message::SEVERITY_WARNING, [], 1437833387);
                $this->redirect('show', null, null, ['workspace' => $targetWorkspace]);
            }

            $personalWorkspace->setBaseWorkspace($targetWorkspace);
            $this->workspaceFinder->update($personalWorkspace);
        }

        $contextProperties = $targetNode->getContext()->getProperties();
        $contextProperties['workspaceName'] = $personalWorkspace->getName();
        $context = $this->contextFactory->create($contextProperties);

        $mainRequest = $this->controllerContext->getRequest()->getMainRequest();
        /** @var ActionRequest $mainRequest */
        $this->uriBuilder->setRequest($mainRequest);

        if ($this->packageManager->isPackageAvailable('Neos.Neos.Ui')) {
            $this->redirect('index', 'Backend', 'Neos.Neos.Ui', ['node' => $context->getNode($targetNode->getPath())]);
        }

        $this->redirect('show', 'Frontend\\Node', 'Neos.Neos', ['node' => $context->getNode($targetNode->getPath())]);
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
        $this->addFlashMessage($this->translator->translateById('workspaces.selectedChangeHasBeenPublished', [], null, null, 'Modules', 'Neos.Neos'));
        $this->redirect('show', null, null, ['workspace' => $selectedWorkspace]);
    }

    /**
     * Discard a a single node
     *
     * @param NodeInterface $node
     * @param Workspace $selectedWorkspace
     * @throws WorkspaceException
     */
    public function discardNodeAction(NodeInterface $node, Workspace $selectedWorkspace)
    {
        // Hint: we cannot use $node->remove() here, as this removes the node recursively (but we just want to *discard changes*)
        $this->publishingService->discardNode($node);
        $this->addFlashMessage($this->translator->translateById('workspaces.selectedChangeHasBeenDiscarded', [], null, null, 'Modules', 'Neos.Neos'));
        $this->redirect('show', null, null, ['workspace' => $selectedWorkspace]);
    }

    /**
     * Publishes or discards the given nodes
     *
     * @param array $nodes <\Neos\ContentRepository\Domain\Model\NodeInterface> $nodes
     * @param string $action
     * @param Workspace $selectedWorkspace
     * @throws \Exception
     * @throws \Neos\Flow\Property\Exception
     * @throws \Neos\Flow\Security\Exception
     */
    public function publishOrDiscardNodesAction(array $nodes, $action, Workspace $selectedWorkspace = null)
    {
        $propertyMappingConfiguration = $this->propertyMapper->buildPropertyMappingConfiguration();
        $propertyMappingConfiguration->setTypeConverterOption(NodeConverter::class, NodeConverter::REMOVED_CONTENT_SHOWN, true);
        foreach ($nodes as $key => $node) {
            $nodes[$key] = $this->propertyMapper->convert($node, NodeInterface::class, $propertyMappingConfiguration);
        }
        switch ($action) {
            case 'publish':
                foreach ($nodes as $node) {
                    $this->publishingService->publishNode($node);
                }
                $this->addFlashMessage($this->translator->translateById('workspaces.selectedChangesHaveBeenPublished', [], null, null, 'Modules', 'Neos.Neos'));
            break;
            case 'discard':
                $this->publishingService->discardNodes($nodes);
                $this->addFlashMessage($this->translator->translateById('workspaces.selectedChangesHaveBeenDiscarded', [], null, null, 'Modules', 'Neos.Neos'));
            break;
            default:
                throw new \RuntimeException('Invalid action "' . htmlspecialchars($action) . '" given.', 1346167441);
        }

        $this->redirect('show', null, null, ['workspace' => $selectedWorkspace]);
    }

    /**
     * Publishes the whole workspace
     *
     * @param WorkspaceName $workspace
     */
    public function publishWorkspaceAction(WorkspaceName $workspace)
    {
        $workspace = $this->workspaceFinder->findOneByName($workspace);
        $baseWorkspace = $this->workspaceFinder->findOneByName($workspace->getBaseWorkspaceName());

        $this->workspaceCommandHandler->handlePublishWorkspace(new PublishWorkspace($workspace->getWorkspaceName(), $this->getCurrentUserIdentifier()))->blockUntilProjectionsAreUpToDate();
        $this->addFlashMessage($this->translator->translateById('workspaces.allChangesInWorkspaceHaveBeenPublished', [htmlspecialchars($workspace->getWorkspaceName()->getName()), htmlspecialchars($baseWorkspace->getWorkspaceName()->getName())], null, null, 'Modules', 'Neos.Neos'));
        $this->redirect('index');
    }

    /**
     * Discards content of the whole workspace
     *
     * @param WorkspaceName $workspace
     */
    public function discardWorkspaceAction(WorkspaceName $workspace)
    {
        $this->workspaceCommandHandler->handleDiscardWorkspace(new DiscardWorkspace($workspace, $this->getCurrentUserIdentifier()))->blockUntilProjectionsAreUpToDate();

        $this->addFlashMessage($this->translator->translateById('workspaces.allChangesInWorkspaceHaveBeenDiscarded', [htmlspecialchars($workspace->getName())], null, null, 'Modules', 'Neos.Neos'));
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
     *
     * @param Workspace $selectedWorkspace
     * @return array
     */
    protected function computeSiteChanges(Workspace $selectedWorkspace)
    {
        $siteChanges = [];
        $changes = $this->changeFinder->findByContentStreamIdentifier($selectedWorkspace->getCurrentContentStreamIdentifier());

        foreach ($changes as $change) {
            $contentStreamIdentifier = $change->contentStreamIdentifier;

            if ($change->deleted) {
                // If we deleted a node, there is no way for us to anymore find the deleted node in the ContentStream where the node
                // was deleted.
                // Thus, to figure out the rootline for display, we check the *base workspace* Content Stream.
                //
                // This is safe because the UI basically shows what would be removed once the deletion is published.
                $baseWorkspace = $this->workspaceFinder->findOneByName($selectedWorkspace->getBaseWorkspaceName());
                $contentStreamIdentifier = $baseWorkspace->getCurrentContentStreamIdentifier();
            }
            $subgraph = $this->contentGraph->getSubgraphByIdentifier($contentStreamIdentifier, $change->originDimensionSpacePoint, VisibilityConstraints::withoutRestrictions());

            $node = $subgraph->findNodeByNodeAggregateIdentifier($change->nodeAggregateIdentifier);
            $pathParts = explode('/', (string)$node->findNodePath());
            if (count($pathParts) > 2) {
                $siteNodeName = $pathParts[2];
                $q = new FlowQuery([$node]);
                $document = $q->closest('[instanceof Neos.Neos:Document]')->get(0);

                // $document will be null if we have a broken root line for this node. This actually should never happen, but currently can in some scenarios.
                if ($document !== null) {
                    $documentPath = implode('/', array_slice(explode('/', (string)$document->findNodePath()), 3));
                    $relativePath = str_replace(sprintf('//%s/%s', $siteNodeName, $documentPath), '', $node->findNodePath());
                    if (!isset($siteChanges[$siteNodeName]['siteNode'])) {
                        $siteChanges[$siteNodeName]['siteNode'] = $this->siteRepository->findOneByNodeName($siteNodeName);
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

        ksort($siteChanges);
        foreach ($siteChanges as $siteKey => $site) {
            /*foreach ($site['documents'] as $documentKey => $document) {
                $liveDocumentNode = $liveContext->getNodeByIdentifier($document['documentNode']->getIdentifier());
                $siteChanges[$siteKey]['documents'][$documentKey]['isMoved'] = $liveDocumentNode && $document['documentNode']->getPath() !== $liveDocumentNode->getPath();
                $siteChanges[$siteKey]['documents'][$documentKey]['isNew'] = $liveDocumentNode === null;
                foreach ($document['changes'] as $changeKey => $change) {
                    $liveNode = $liveContext->getNodeByIdentifier($change['node']->getIdentifier());
                    $siteChanges[$siteKey]['documents'][$documentKey]['changes'][$changeKey]['isNew'] = is_null($liveNode);
                    $siteChanges[$siteKey]['documents'][$documentKey]['changes'][$changeKey]['isMoved'] = $liveNode && $change['node']->getPath() !== $liveNode->getPath();
                }
            }*/
            ksort($siteChanges[$siteKey]['documents']);
        }
        return $siteChanges;
    }

    /**
     * Retrieves the given node's corresponding node in the base content stream (that is, which would be overwritten if the
     * given node would be published)
     */
    protected function getOriginalNode(NodeInterface $modifiedNode, ContentStreamIdentifier $baseContentStreamIdentifier): ?NodeInterface
    {
        $baseSubgraph = $this->contentGraph->getSubgraphByIdentifier($baseContentStreamIdentifier, $modifiedNode->getDimensionSpacePoint(), VisibilityConstraints::withoutRestrictions());
        $node = $baseSubgraph->findNodeByNodeAggregateIdentifier($modifiedNode->getNodeAggregateIdentifier());

        return $node;
    }

    /**
     * Renders the difference between the original and the changed content of the given node and returns it, along
     * with meta information, in an array.
     *
     * @param NodeInterface $changedNode
     * @return array
     */
    protected function renderContentChanges(NodeInterface $changedNode, ContentStreamIdentifier $contentStreamIdentifierOfOriginalNode)
    {
        $currentWorkspace = $this->workspaceFinder->findOneByCurrentContentStreamIdentifier($contentStreamIdentifierOfOriginalNode);
        $originalNode = null;
        if ($currentWorkspace !== null) {
            $baseWorkspace = $this->workspaceFinder->findOneByName($currentWorkspace->getBaseWorkspaceName());
            $baseContentStreamIdentifier = $baseWorkspace->getCurrentContentStreamIdentifier();
            $originalNode = $this->getOriginalNode($changedNode, $baseContentStreamIdentifier);
        }


        $contentChanges = [];

        $changeNodePropertiesDefaults = $changedNode->getNodeType()->getDefaultValuesForProperties();

        $renderer = new HtmlArrayRenderer();
        foreach ($changedNode->getProperties() as $propertyName => $changedPropertyValue) {
            if ($originalNode === null && empty($changedPropertyValue) || (isset($changeNodePropertiesDefaults[$propertyName]) && $changedPropertyValue === $changeNodePropertiesDefaults[$propertyName])) {
                continue;
            }

            $originalPropertyValue = ($originalNode === null ? null : $originalNode->getProperty($propertyName));

            if ($changedPropertyValue === $originalPropertyValue) {
                // TODO  && !$changedNode->isRemoved()
                continue;
            }

            if (!is_object($originalPropertyValue) && !is_object($changedPropertyValue)) {
                $originalSlimmedDownContent = $this->renderSlimmedDownContent($originalPropertyValue);
                // TODO $changedSlimmedDownContent = $changedNode->isRemoved() ? '' : $this->renderSlimmedDownContent($changedPropertyValue);
                $changedSlimmedDownContent = $this->renderSlimmedDownContent($changedPropertyValue);

                $diff = new Diff(explode("\n", $originalSlimmedDownContent), explode("\n", $changedSlimmedDownContent), ['context' => 1]);
                $diffArray = $diff->render($renderer);
                $this->postProcessDiffArray($diffArray);

                if (count($diffArray) > 0) {
                    $contentChanges[$propertyName] = [
                        'type' => 'text',
                        'propertyLabel' => $this->getPropertyLabel($propertyName, $changedNode),
                        'diff' => $diffArray
                    ];
                }
                // The && in belows condition is on purpose as creating a thumbnail for comparison only works if actually
                // BOTH are ImageInterface (or NULL).
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
            } elseif ($originalPropertyValue instanceof AssetInterface || $changedPropertyValue instanceof AssetInterface) {
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
            $contentSnippet = preg_replace('/<br[^>]*>/', "\n", $propertyValue);
            $contentSnippet = preg_replace('/<[^>]*>/', ' ', $contentSnippet);
            $contentSnippet = str_replace('&nbsp;', ' ', $contentSnippet);
            $content = trim(preg_replace('/ {2,}/', ' ', $contentSnippet));
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
     * @param array $diffArray
     * @return void
     */
    protected function postProcessDiffArray(array &$diffArray)
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
     * @return array
     */
    protected function prepareBaseWorkspaceOptions(Workspace $excludedWorkspace = null)
    {
        $baseWorkspaceOptions = [];
        foreach ($this->workspaceFinder->findAll() as $workspace) {
            /** @var Workspace $workspace */
            if (!$workspace->isPersonalWorkspace() && $workspace !== $excludedWorkspace && ($workspace->isPublicWorkspace() || $workspace->isInternalWorkspace() || $this->userService->currentUserCanManageWorkspace($workspace))) {
                $baseWorkspaceOptions[(string)$workspace->getWorkspaceName()] = (string)$workspace->getWorkspaceTitle();
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

    private function getCurrentUserIdentifier(): UserIdentifier
    {
        return UserIdentifier::fromString(
            $this->persistenceManager->getIdentifierByObject($this->userService->getCurrentUser())
        );
    }
}
