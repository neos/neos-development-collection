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
use TYPO3\Neos\Controller\Exception\NodeCreationException;
use TYPO3\Neos\Domain\Repository\DomainRepository;
use TYPO3\Neos\Domain\Repository\SiteRepository;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\Neos\Domain\Service\ContentContextFactory;
use TYPO3\Neos\Domain\Service\NodeSearchService;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Model\NodeType;
use TYPO3\TYPO3CR\Domain\Service\Context;
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
	 * @var ContentContextFactory
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
			$this->addExistingNodeVariantInformationToResponse($identifier, $contentContext);
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
	 * Create a new node from an existing one
	 *
	 * The "mode" property defines the basic mode of operation. Currently supported modes:
	 *
	 * 'adoptFromAnotherDimension': Adopts the single node from another dimension
	 *   - $identifier, $workspaceName and $sourceDimensions specify the source node
	 *   - $identifier, $workspaceName and $dimensions specify the target node
	 *
	 * @param string $mode
	 * @param string $identifier Specifies the identifier of the node to be created; if source
	 * @param string $workspaceName Name of the workspace where to create the node in
	 * @param array $dimensions Optional list of dimensions and their values in which the node should be created
	 * @param array $sourceDimensions
	 * @return string
	 */
	public function createAction($mode, $identifier, $workspaceName = 'live', array $dimensions = array(), array $sourceDimensions = array()) {
		if ($mode === 'adoptFromAnotherDimension' || $mode === 'adoptFromAnotherDimensionAndCopyContent') {
			$originalContentContext = $this->createContentContext($workspaceName, $sourceDimensions);
			$node = $originalContentContext->getNodeByIdentifier($identifier);

			if ($node === NULL) {
				$this->throwStatus(404, 'Original node was not found.');
			}

			$contentContext = $this->createContentContext($workspaceName, $dimensions);

			$this->adoptNodeAndParents($node, $contentContext, $mode === 'adoptFromAnotherDimensionAndCopyContent');

			$this->redirect('show', NULL, NULL, array(
				'identifier' => $identifier,
				'workspaceName' => $workspaceName,
				'dimensions' => $dimensions
			));
		} else {
			throw new NodeCreationException(sprintf('The create mode "%s" is not supported.', $mode), 1415105055);
		}
	}

	/**
	 * Create a ContentContext based on the given workspace name
	 *
	 * @param string $workspaceName Name of the workspace to set for the context
	 * @param array $dimensions Optional list of dimensions and their values which should be set
	 * @return ContentContext
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

	/**
	 * If the node is not found, we *first* want to figure out whether the node exists in other dimensions or is really non-existant
	 *
	 * @param $identifier
	 * @param Context $context
	 * @return void
	 */
	protected function addExistingNodeVariantInformationToResponse($identifier, Context $context) {
		$nodeVariants = $context->getNodeVariantsByIdentifier($identifier);
		if (count($nodeVariants) > 0) {
			$this->response->setHeader('X-Neos-Node-Exists-In-Other-Dimensions', TRUE);

			// If the node exists in another dimension, we want to know how many nodes in the rootline are also missing for the target
			// dimension. This is needed in the UI to tell the user if nodes will be materialized recursively upwards in the rootline.
			// To find the node path for the given identifier, we just use the first result. This is a safe assumption at least for
			// "Document" nodes (aggregate=TRUE), because they are always moved in-sync.
			$node = reset($nodeVariants);
			if ($node->getNodeType()->isAggregate()) {
				$pathSegments = count(explode('/', $node->getPath()));
				$nodes = $context->getNodesOnPath('/', $node->getPath());
				// We subtract 3 because:
				// - /sites/ is never translated (first part of the rootline)
				// - the actual document is not translated either (last part of the rootline). Otherwise, we wouldn't be inside this IF-branch.
				// - we count the number of path segments, and the first path segment (before the / which indicates an absolute path) is always emtpty.
				$this->response->setHeader('X-Neos-Nodes-Missing-On-Rootline', $pathSegments - count($nodes) - 3);
			}
		}
	}

	/**
	 * Adopt (translate) the given node and parents that are not yet visible to the given context
	 *
	 * @param NodeInterface $node
	 * @param ContentContext $contentContext
	 * @param boolean $copyContent TRUE if the content from the nodes that are translated should be copied
	 * @return void
	 */
	protected function adoptNodeAndParents(NodeInterface $node, ContentContext $contentContext, $copyContent) {
		$contentContext->adoptNode($node, $copyContent);

		$parentNode = $node;
		while ($parentNode = $parentNode->getParent()) {
			$visibleInContext = $contentContext->getNodeByIdentifier($parentNode->getIdentifier()) !== NULL;
			if ($parentNode->getPath() !== '/' && $parentNode->getPath() !== '/sites' && !$visibleInContext) {
				$contentContext->adoptNode($parentNode, $copyContent);
			}
		}
	}
}
