<?php
namespace TYPO3\Neos\Command;

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
use TYPO3\TYPO3CR\Domain\Model\Workspace;

/**
 * The Workspace Command Controller
 *
 * @Flow\Scope("singleton")
 */
class WorkspaceCommandController extends \TYPO3\Flow\Cli\CommandController {

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
	 * Publish everything in the workspace with the given workspace name.
	 *
	 * @param string $workspaceName
	 * @param boolean $verbose
	 * @return void
	 */
	public function publishAllCommand($workspaceName, $verbose = FALSE) {
		$workspace = $this->workspaceRepository->findOneByName($workspaceName);
		if (!$workspace instanceof Workspace) {
			$this->outputLine('Workspace "%s" does not exist', array($workspaceName));
			$this->quit(1);
		}

		try {
			$nodes = $this->publishingService->getUnpublishedNodes($workspace);
		} catch (\Exception $exception) {
			$this->outputLine('An error occurred while fetching unpublished nodes from workspace %s, publish aborted.', array($workspaceName));
			$this->quit(1);
		}

		$this->outputLine('The workspace %s contains %u unpublished nodes.', array($workspaceName, count($nodes)));

		foreach ($nodes as $node) {
			/** @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node */
			if ($verbose) {
				$this->outputLine('    ' . $node->getPath());
			}
			$this->publishingService->publishNode($node);
		}

		$this->outputLine('Published all nodes in workspace %s', array($workspaceName));
	}

	/**
	 * Discard everything in the workspace with the given workspace name.
	 *
	 * @param string $workspaceName
	 * @param boolean $verbose
	 * @return void
	 */
	public function discardAllCommand($workspaceName, $verbose = FALSE) {
		$workspace = $this->workspaceRepository->findOneByName($workspaceName);
		if (!$workspace instanceof Workspace) {
			$this->outputLine('Workspace "%s" does not exist', array($workspaceName));
			$this->quit(1);
		}

		try {
			$nodes = $this->publishingService->getUnpublishedNodes($workspace);
		} catch (\Exception $exception) {
			$this->outputLine('An error occurred while fetching unpublished nodes from workspace %s, discard aborted.', array($workspaceName));
			$this->quit(1);
		}

		$this->outputLine('The workspace %s contains %u unpublished nodes.', array($workspaceName, count($nodes)));

		foreach ($nodes as $node) {
			/** @var \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node */
			if ($node->getPath() !== '/') {
				if ($verbose) {
					$this->outputLine('    ' . $node->getPath());
				}
				$this->publishingService->discardNode($node);
			}
		}

		$this->outputLine('Discarded all nodes in workspace %s', array($workspaceName));
	}

	/**
	 * Display a list of existing workspaces
	 *
	 * @return void
	 */
	public function listCommand() {
		$workspaces = $this->workspaceRepository->findAll();

		if ($workspaces->count() === 0) {
			$this->outputLine('No workspaces found.');
			exit(0);
		}

		$workspaceNames = array();
		foreach ($workspaces as $workspace) {
			$workspaceNames[$workspace->getName()] = $workspace->getBaseWorkspace() ? $workspace->getBaseWorkspace()->getName() : '';
		}
		ksort($workspaceNames);

		$longestName = max(array_map('strlen', array_keys($workspaceNames)));
		$this->outputLine(' <b>' . str_pad('Workspace', $longestName + 10) . 'Base workspace</b>');
		foreach ($workspaceNames as $workspaceName => $baseWorkspaceName) {
			$this->outputLine(' ' . str_pad($workspaceName, $longestName + 10) . $baseWorkspaceName);
		}
	}
}
