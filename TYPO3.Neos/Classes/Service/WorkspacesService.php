<?php
namespace TYPO3\TYPO3\Service;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3.TYPO3".                *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * The workspaces service adds some basic helper methods for getting workspaces,
 * unpublished nodes and methods for publishing nodes or whole workspaces.
 *
 * @FLOW3\Scope("singleton")
 */
class WorkspacesService {

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @return \TYPO3\FLOW3\Persistence\QueryResultInterface
	 */
	public function getCurrentWorkspace() {
		return $this->nodeRepository->getContext()->getWorkspace(FALSE);
	}

	/**
	 * @param string $workspaceName
	 * @return \TYPO3\TYPO3CR\Domain\Model\Workspace
	 */
	public function getWorkspace($workspaceName) {
		return $this->workspaceRepository->findOneByName($workspaceName);
	}

	/**
	 * @return \TYPO3\FLOW3\Persistence\QueryResultInterface
	 */
	public function getWorkspaces() {
		return $this->workspaceRepository->findAll();
	}

	/**
	 * @param string $workspaceName
	 * @return \TYPO3\FLOW3\Persistence\QueryResultInterface
	 */
	public function getUnpublishedNodes($workspaceName) {
		return $this->nodeRepository->findByWorkspace($this->workspaceRepository->findOneByName($workspaceName));
	}

	/**
	 * @param string $targetWorkspaceName
	 * @return integer
	 */
	public function getUnpublishedNodesCount($targetWorkspaceName) {
		return $this->workspaceRepository->findOneByName($targetWorkspaceName)->getNodeCount() - 1;
	}

	/**
	 * @param string $workspaceName
	 * @param string $targetWorkspaceName
	 * @return void
	 */
	public function publishWorkspace($workspaceName, $targetWorkspaceName = 'live') {
		$this->workspaceRepository->findOneByName($workspaceName)->publish($targetWorkspaceName);
	}

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param $targetWorkspaceName
	 * @return void
	 */
	public function publishNode(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, $targetWorkspaceName = 'live') {
		$nodes = array($node);
		$contentType = $node->getContentType();
		if ($contentType->isOfType('TYPO3.Phoenix.ContentTypes:Page') || $contentType->hasStructure()) {
			foreach ($node->getChildNodes('TYPO3.Phoenix.ContentTypes:Section') as $sectionNode) {
				array_push($nodes, $sectionNode);
			}
		}
		$sourceWorkspace = $node->getWorkspace();
		$sourceWorkspace->publishNodes($nodes, $targetWorkspaceName);
	}

	/**
	 * @param array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface> $nodes
	 * @param $targetWorkspaceName
	 * @return void
	 */
	public function publishNodes(array $nodes, $targetWorkspaceName = 'live') {
		foreach ($nodes as $node) {
			$this->publishNode($node, $targetWorkspaceName);
		}
	}

}
?>