<?php
namespace TYPO3\Neos\Controller\Service;

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
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\TYPO3CR\Domain\Model\NodeType;

/**
 * Controller for displaying nodes in the frontend
 *
 * @Flow\Scope("singleton")
 */
class NodeController extends ActionController {

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
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Service\NodeSearchService
	 */
	protected $nodeSearchService;

	/**
	 * Shows a list of nodes
	 *
	 * @param string $searchTerm
	 * @param string $workspaceName
	 * @param array $nodeTypes
	 * @return string
	 */
	public function indexAction($searchTerm, $workspaceName = 'live', array $nodeTypes = array('TYPO3.Neos:Document')) {
		$searchableNodeTypeNames = array();
		foreach ($nodeTypes as $nodeTypeName) {
			if (!$this->nodeTypeManager->hasNodeType($nodeTypeName)) {
				$this->throwStatus(400, sprintf('Unknown node type "%s"', $nodeTypeName));
			}

			$searchableNodeTypeNames[$nodeTypeName] = $nodeTypeName;
			/** @var NodeType $subNodeType */
			foreach ($this->nodeTypeManager->getSubNodeTypes($nodeTypeName, FALSE) as $subNodeTypeName => $subNodeType) {
				$searchableNodeTypeNames[$subNodeTypeName] = $subNodeTypeName;
			}
		}

		$contentContext = $this->createContentContext($workspaceName);
		$nodes = $this->nodeSearchService->findByProperties($searchTerm, $searchableNodeTypeNames, $contentContext);
		$this->view->assign('nodes', $nodes);
	}

	/**
	 * Shows a specific node
	 *
	 * @param string $identifier
	 * @param string $workspaceName
	 * @return string
	 */
	public function showAction($identifier, $workspaceName = 'live') {
		$contentContext = $this->createContentContext($workspaceName);
		$node = $contentContext->getNodeByIdentifier($identifier);

		$this->view->assign('node', $node);
	}

	/**
	 * Create a Content Context based on the given workspace name
	 *
	 * @param string $workspaceName
	 * @return \TYPO3\TYPO3CR\Domain\Service\Context
	 */
	protected function createContentContext($workspaceName) {
		$contextProperties = array('workspaceName' => $workspaceName);
		$currentDomain = $this->domainRepository->findOneByActiveRequest();
		if ($currentDomain !== NULL) {
			$contextProperties['currentSite'] = $currentDomain->getSite();
			$contextProperties['currentDomain'] = $currentDomain;
		} else {
			$contextProperties['currentSite'] = $this->siteRepository->findOnline()->getFirst();
		}
		return $this->contextFactory->create($contextProperties);
	}

}
?>
