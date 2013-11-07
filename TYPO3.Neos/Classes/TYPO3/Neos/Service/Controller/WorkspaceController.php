<?php
namespace TYPO3\Neos\Service\Controller;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Service Controller for managing Workspaces
 */
class WorkspaceController extends AbstractServiceController {

	/**
	 * @var string
	 */
	protected $defaultViewObjectName = 'TYPO3\Neos\Service\View\NodeView';

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Service\PublishingService
	 */
	protected $publishingService;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @return void
	 */
	protected function initializeAction() {
		if ($this->arguments->hasArgument('node')) {
			$this
				->arguments
				->getArgument('node')
				->getPropertyMappingConfiguration()
				->setTypeConverterOption('TYPO3\TYPO3CR\TypeConverter\NodeConverter', \TYPO3\TYPO3CR\TypeConverter\NodeConverter::REMOVED_CONTENT_SHOWN, TRUE);
		}
	}

	/**
	 * Publishes the given node to the specified targetWorkspace
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param string $targetWorkspaceName
	 * @return void
	 */
	public function publishNodeAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, $targetWorkspaceName) {
		$targetWorkspace = $this->workspaceRepository->findOneByName($targetWorkspaceName);
		/**
		 * TODO: The publishing pushes the same node twice, which causes the node to be published
		 * already when it's processed the second time. This obviously leads to a problem for the
		 * Workspace object which will (in the second time) try to publish a node in the live workspace
		 * to the baseWorkspace of the live workspace (which does not exist).
		 */
		if ($targetWorkspace === $node->getWorkspace()) {
			$this->throwStatus(204, 'Node has been published');
			return;
		}

		$this->publishingService->publishNode($node, $targetWorkspace);

		$this->throwStatus(204, 'Node has been published');
	}

	/**
	 * Discards the given node
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @return void
	 */
	public function discardNodeAction(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		$this->publishingService->discardNode($node);

		$this->throwStatus(204, 'Node changes have been discarded');
	}

	/**
	 * Publish everything in the workspace with the given workspace name
	 *
	 * @param string $workspaceName
	 * @return void
	 */
	public function publishAllAction($workspaceName) {
		$workspace = $this->workspaceRepository->findOneByName($workspaceName);
		$this->publishingService->publishNodes($this->publishingService->getUnpublishedNodes($workspace));

		$this->throwStatus(204, 'Workspace changes have been published');
	}

	/**
	 * Get every unpublished node in the workspace with the given workspace name
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace
	 * @return void
	 */
	public function getWorkspaceWideUnpublishedNodesAction($workspace) {
		$nodes = $this->publishingService->getUnpublishedNodes($workspace);

		$this->view->assignNodes($nodes);
	}

	/**
	 * Discard everything in the workspace with the given workspace name
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\Workspace $workspace
	 * @return void
	 */
	public function discardAllAction($workspace) {
		$this->publishingService->discardNodes($this->publishingService->getUnpublishedNodes($workspace));

		$this->throwStatus(204, 'Workspace changes have been discarded');
	}

}
