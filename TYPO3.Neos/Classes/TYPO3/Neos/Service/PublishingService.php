<?php
namespace TYPO3\Neos\Service;

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
 * The workspaces service adds some basic helper methods for getting workspaces,
 * unpublished nodes and methods for publishing nodes or whole workspaces.
 *
 * @Flow\Scope("singleton")
 */
class PublishingService {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Factory\NodeFactory
	 */
	protected $nodeFactory;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Repository\DomainRepository
	 */
	protected $domainRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Repository\SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @param string $workspaceName
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface>
	 */
	public function getUnpublishedNodes($workspaceName) {
		$finalNodes = array();
		$contextProperties = array(
			'workspaceName' => $workspaceName,
			'inaccessibleContentShown' => TRUE,
			'invisibleContentShown' => TRUE,
			'removedContentShown' => TRUE
		);

		$currentDomain = $this->domainRepository->findOneByActiveRequest();
		if ($currentDomain !== NULL) {
			$contextProperties['currentSite'] = $currentDomain->getSite();
			$contextProperties['currentDomain'] = $currentDomain;
		} else {
			$contextProperties['currentSite'] = $this->siteRepository->findFirst();
		}
		$contentContext = $this->contextFactory->create($contextProperties);

		$nodeData = $this->nodeDataRepository->findByWorkspace($contentContext->getWorkspace(FALSE));
		foreach ($nodeData as $singleNodeData) {
			$node = $this->nodeFactory->createFromNodeData($singleNodeData, $contentContext);
			if ($node !== NULL) {
				$finalNodes[] = $node;
			}
		}
		return $finalNodes;
	}

	/**
	 * @param string $targetWorkspaceName
	 * @return integer
	 */
	public function getUnpublishedNodesCount($targetWorkspaceName) {
		return $this->workspaceRepository->findOneByName($targetWorkspaceName)->getNodeCount() - 1;
	}

	/**
	 * @param \TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param string $targetWorkspaceName
	 * @return void
	 */
	public function publishNode(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, $targetWorkspaceName = 'live') {
		$nodes = array($node);
		$nodeType = $node->getNodeType();
		if ($nodeType->isOfType('TYPO3.Neos:Document') || $nodeType->hasChildNodes()) {
			foreach ($node->getChildNodes('TYPO3.Neos:ContentCollection') as $contentCollectionNode) {
				array_push($nodes, $contentCollectionNode);
			}
		}
		$sourceWorkspace = $node->getWorkspace();
		$sourceWorkspace->publishNodes($nodes, $targetWorkspaceName);
	}

	/**
	 * @param array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface> $nodes
	 * @param string $targetWorkspaceName
	 * @return void
	 */
	public function publishNodes(array $nodes, $targetWorkspaceName = 'live') {
		foreach ($nodes as $node) {
			$this->publishNode($node, $targetWorkspaceName);
		}
	}

}
?>