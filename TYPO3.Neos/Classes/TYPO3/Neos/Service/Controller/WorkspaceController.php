<?php
namespace TYPO3\Neos\Service\Controller;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Flow\Property\PropertyMappingConfigurationBuilder;
use TYPO3\Neos\Service\PublishingService;
use TYPO3\Neos\Service\View\NodeView;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\Workspace;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;
use TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository;
use TYPO3\TYPO3CR\TypeConverter\NodeConverter;

/**
 * Service Controller for managing Workspaces
 */
class WorkspaceController extends AbstractServiceController
{
    /**
     * @var string
     */
    protected $defaultViewObjectName = NodeView::class;

    /**
     * @var NodeView
     */
    protected $view;

    /**
     * @Flow\Inject
     * @var NodeDataRepository
     */
    protected $nodeDataRepository;

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
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @Flow\Inject
     * @var PropertyMappingConfigurationBuilder
     */
    protected $propertyMappingConfigurationBuilder;

    /**
     * @return void
     */
    protected function initializeAction()
    {
        if ($this->arguments->hasArgument('node')) {
            $this
                ->arguments
                ->getArgument('node')
                ->getPropertyMappingConfiguration()
                ->setTypeConverterOption(NodeConverter::class, NodeConverter::REMOVED_CONTENT_SHOWN, true);
        }

        if ($this->arguments->hasArgument('nodes')) {
            $this
                ->arguments
                ->getArgument('nodes')
                ->getPropertyMappingConfiguration()
                ->forProperty('*')
                ->setTypeConverterOption(NodeConverter::class, NodeConverter::REMOVED_CONTENT_SHOWN, true);
        }
    }

    /**
     * Publishes the given node to the specified targetWorkspace
     *
     * @param NodeInterface $node
     * @param string $targetWorkspaceName
     * @return void
     */
    public function publishNodeAction(NodeInterface $node, $targetWorkspaceName = null)
    {
        $targetWorkspace = ($targetWorkspaceName !== null) ? $this->workspaceRepository->findOneByName($targetWorkspaceName) : null;
        $this->publishingService->publishNode($node, $targetWorkspace);

        $this->throwStatus(204, 'Node published', '');
    }

    /**
     * Publishes the given nodes to the specified targetWorkspace
     *
     * @param array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface> $nodes
     * @param string $targetWorkspaceName
     * @return void
     */
    public function publishNodesAction(array $nodes, $targetWorkspaceName  = null)
    {
        $targetWorkspace = ($targetWorkspaceName !== null) ? $this->workspaceRepository->findOneByName($targetWorkspaceName) : null;
        $this->publishingService->publishNodes($nodes, $targetWorkspace);

        $this->throwStatus(204, 'Nodes published', '');
    }

    /**
     * Discards the given node
     *
     * @param NodeInterface $node
     * @return void
     */
    public function discardNodeAction(NodeInterface $node)
    {
        $this->publishingService->discardNode($node);

        $this->throwStatus(204, 'Node changes have been discarded', '');
    }

    /**
     * Discards the given nodes
     *
     * @param array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface> $nodes
     * @return void
     */
    public function discardNodesAction(array $nodes)
    {
        $this->publishingService->discardNodes($nodes);

        $this->throwStatus(204, 'Node changes have been discarded', '');
    }

    /**
     * Publish everything in the workspace with the given workspace name
     *
     * @param string $sourceWorkspaceName Name of the source workspace containing the content to publish
     * @param string $targetWorkspaceName Name of the target workspace the content should be published to
     * @return void
     */
    public function publishAllAction($sourceWorkspaceName, $targetWorkspaceName)
    {
        $sourceWorkspace = $this->workspaceRepository->findOneByName($sourceWorkspaceName);
        $targetWorkspace = $this->workspaceRepository->findOneByName($targetWorkspaceName);
        if ($sourceWorkspace === null) {
            $this->throwStatus(400, 'Invalid source workspace');
        }
        if ($targetWorkspace === null) {
            $this->throwStatus(400, 'Invalid target workspace');
        }
        $this->publishingService->publishNodes($this->publishingService->getUnpublishedNodes($sourceWorkspace), $targetWorkspace);

        $this->throwStatus(204, sprintf('All changes in workspace %s have been published to %s', $sourceWorkspaceName, $targetWorkspaceName), '');
    }

    /**
     * Get every unpublished node in the workspace with the given workspace name
     *
     * @param Workspace $workspace
     * @return void
     */
    public function getWorkspaceWideUnpublishedNodesAction($workspace)
    {
        $this->view->assignNodes($this->publishingService->getUnpublishedNodes($workspace));
    }

    /**
     * Discard everything in the workspace with the given workspace name
     *
     * @param Workspace $workspace
     * @return void
     */
    public function discardAllAction($workspace)
    {
        $this->publishingService->discardAllNodes($workspace);

        $this->throwStatus(204, 'Workspace changes have been discarded', '');
    }
}
