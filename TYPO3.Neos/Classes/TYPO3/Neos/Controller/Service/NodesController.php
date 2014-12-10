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

use TYPO3\Eel\FlowQuery\FlowQuery;
use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Mvc\Controller\ActionController;
use TYPO3\Flow\Property\PropertyMapper;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Domain\Service\NodeSearchService;
use TYPO3\Neos\View\Service\NodeJsonView;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TYPO3CR\Domain\Service\NodeTypeManager;

/**
 * Rudimentary REST service for nodes
 *
 * @Flow\Scope("singleton")
 */
class NodesController extends ActionController {

	/**
	 * @Flow\Inject
	 * @var DomainRepository
	 */
	protected $domainRepository;

	/**
	 * @Flow\Inject
	 * @var SiteRepository
	 */
	protected $siteRepository;

	/**
	 * @Flow\Inject
	 * @var ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject
	 * @var NodeTypeManager
	 */
	protected $nodeTypeManager;

	/**
	 * @Flow\Inject
	 * @var NodeSearchService
	 */
	protected $nodeSearchService;

	/**
	 * @Flow\Inject
	 * @var PropertyMapper
	 */
	protected $propertyMapper;

	/**
	 * @var array
	 */
	protected $viewFormatToObjectNameMap = array(
		'html' => 'TYPO3\Fluid\View\TemplateView',
		'json' => 'TYPO3\Neos\View\Service\NodeJsonView'
	);

	/**
	 * A list of IANA media types which are supported by this controller
	 *
	 * @var array
	 * @see http://www.iana.org/assignments/media-types/index.html
	 */
	protected $supportedMediaTypes = array(
		'text/html',
		'application/json'
	);

	/**
	 * Shows a list of nodes
	 *
	 * @param string $searchTerm An optional search term used for filtering the list of nodes
	 * @param string $workspaceName Name of the workspace to search in, "live" by default
	 * @param array $dimensions Optional list of dimensions and their values which should be used for querying
	 * @param array $nodeTypes A list of node types the list should be filtered by
	 * @return string
	 */
	public function indexAction($searchTerm = '', $workspaceName = 'live', array $dimensions = array(), array $nodeTypes = array('TYPO3.Neos:Document')) {
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

		$contentContext = $this->createContentContext($workspaceName, $dimensions);
		$nodes = $this->nodeSearchService->findByProperties($searchTerm, $searchableNodeTypeNames, $contentContext);

		$this->view->assign('nodes', $nodes);
	}

	/**
	 * Shows a specific node
	 *
	 * @param string $identifier Specifies the node to look up
	 * @param string $workspaceName Name of the workspace to use for querying the node
	 * @param array $dimensions Optional list of dimensions and their values which should be used for querying the specified node
	 * @return string
	 */
	public function showAction($identifier, $workspaceName = 'live', array $dimensions = array()) {
		$contentContext = $this->createContentContext($workspaceName, $dimensions);
		/** @var $node NodeInterface */
		$node = $contentContext->getNodeByIdentifier($identifier);

		if ($node === NULL) {
			$this->throwStatus(404);
		}

		$convertedProperties = array();
		foreach ($node->getProperties() as $propertyName => $propertyValue) {
			try {
				$convertedProperties[$propertyName] = $this->propertyMapper->convert($propertyValue, 'string');
			} catch (\TYPO3\Flow\Property\Exception $exception) {
				$convertedProperties[$propertyName] = '';
			}
		}

		$flowQuery = new FlowQuery(array($node));
		$closestDocumentNode = $flowQuery->closest('[instanceof TYPO3.Neos:Document]')->get(0);
		$this->view->assignMultiple(array(
			'node' => $node,
			'closestDocumentNode' => $closestDocumentNode,
			'convertedNodeProperties' => $convertedProperties
		));
	}

	/**
	 * Create a Content Context based on the given workspace name
	 *
	 * @param string $workspaceName Name of the workspace to set for the context
	 * @param array $dimensions Optional list of dimensions and their values which should be set
	 * @return \TYPO3\TYPO3CR\Domain\Service\Context
	 */
	protected function createContentContext($workspaceName, array $dimensions = array()) {
		$contextProperties = array(
			'workspaceName' => $workspaceName,
			'invisibleContentShown' => TRUE,
			'inaccessibleContentShown' => TRUE
		);

		if ($dimensions !== array()) {
			$contextProperties['dimensions'] = $dimensions;
			$contextProperties['targetDimensions'] = array_map(function($dimensionValues) {
				return array_shift($dimensionValues);
			}, $dimensions);
		}

		$currentDomain = $this->domainRepository->findOneByActiveRequest();
		if ($currentDomain !== NULL) {
			$contextProperties['currentSite'] = $currentDomain->getSite();
			$contextProperties['currentDomain'] = $currentDomain;
		} else {
			$contextProperties['currentSite'] = $this->siteRepository->findFirstOnline();
		}

		return $this->contextFactory->create($contextProperties);
	}

}
