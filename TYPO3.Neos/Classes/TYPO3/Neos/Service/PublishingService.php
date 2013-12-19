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
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\Workspace;

/**
 * The workspaces service adds some basic helper methods for getting workspaces,
 * unpublished nodes and methods for publishing nodes or whole workspaces.
 *
 * @api
 * @Flow\Scope("singleton")
 */
class PublishingService extends \TYPO3\TYPO3CR\Service\PublishingService {

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
	 * Returns a list of nodes contained in the given workspace which are not yet published
	 *
	 * @param Workspace $workspace
	 * @return array<\TYPO3\TYPO3CR\Domain\Model\NodeInterface>
	 * @api
	 */
	public function getUnpublishedNodes(Workspace $workspace) {
		$contextProperties = array(
			'workspaceName' => $workspace->getName(),
			'inaccessibleContentShown' => TRUE,
			'invisibleContentShown' => TRUE,
			'removedContentShown' => TRUE
		);

		$currentDomain = $this->domainRepository->findOneByActiveRequest();
		if ($currentDomain !== NULL) {
			$contextProperties['currentSite'] = $currentDomain->getSite();
			$contextProperties['currentDomain'] = $currentDomain;
		} else {
			$contextProperties['currentSite'] = $this->siteRepository->findOnline()->getFirst();
		}
		$contentContext = $this->contextFactory->create($contextProperties);

		$nodeData = $this->nodeDataRepository->findByWorkspace($workspace);
		$unpublishedNodes = array();
		foreach ($nodeData as $singleNodeData) {
			$node = $this->nodeFactory->createFromNodeData($singleNodeData, $contentContext);
			if ($node !== NULL) {
				$unpublishedNodes[] = $node;
			}
		}
		return $unpublishedNodes;
	}

	/**
	 * Publishes the given node to the specified target workspace. If no workspace is specified, "live" is assumed.
	 *
	 * @param NodeInterface $node
	 * @param Workspace $targetWorkspace If not set the "live" Workspace is assumed to be the publishing target
	 * @return void
	 * @api
	 */
	public function publishNode(NodeInterface $node, Workspace $targetWorkspace = NULL) {
		if ($targetWorkspace === NULL) {
			$targetWorkspace = $this->workspaceRepository->findOneByName('live');
		}
		$nodes = array($node);
		$nodeType = $node->getNodeType();
		if ($nodeType->isOfType('TYPO3.Neos:Document') || $nodeType->hasConfiguration('childNodes')) {
			foreach ($node->getChildNodes('TYPO3.Neos:ContentCollection') as $contentCollectionNode) {
				array_push($nodes, $contentCollectionNode);
			}
		}
		$sourceWorkspace = $node->getWorkspace();
		$sourceWorkspace->publishNodes($nodes, $targetWorkspace);

		$this->emitNodePublished($node, $targetWorkspace);
	}
}
