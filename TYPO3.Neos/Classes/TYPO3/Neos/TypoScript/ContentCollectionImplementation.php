<?php
namespace TYPO3\Neos\TypoScript;

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
use TYPO3\TYPO3CR\Domain\Service\ContextFactoryInterface;
use TYPO3\TypoScript\TypoScriptObjects\AbstractCollectionImplementation;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository;

/**
 * TypoScript implementation to render ContentCollections. Will render needed
 * metadata for removed nodes.
 */
class ContentCollectionImplementation extends AbstractCollectionImplementation {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface
	 */
	protected $accessDecisionManager;

	/**
	 * @Flow\Inject
	 * @var NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var ContextFactoryInterface
	 */
	protected $contextFactory;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Factory\NodeFactory
	 */
	protected $nodeFactory;

	/**
	 * Returns the identifier of the content collection node which shall be rendered
	 *
	 * @return string
	 */
	protected function getNodePath() {
		return $this->tsValue('nodePath');
	}

	/**
	 * @return string
	 */
	public function getTagName() {
		$tagName = $this->tsValue('tagName');
		if ((string)$tagName === '') {
			return 'div';
		} else {
			return $tagName;
		}
	}

	/**
	 * @return NodeInterface
	 */
	public function getContentCollectionNode() {
		$node = $this->getCurrentContextNode();
		if ($node !== NULL) {
			if ($node->getNodeType()->isOfType('TYPO3.Neos:ContentCollection')) {
				$contentCollectionNode = $node;
			} else {
				$contentCollectionNode = $node->getNode($this->getNodePath());
			}

			return $contentCollectionNode;
		}

		return NULL;
	}

	/**
	 * @return array
	 */
	public function getCollection() {
		$contentCollectionNode = $this->getContentCollectionNode();
		if ($contentCollectionNode === NULL) {
			return array();
		}

		if ($contentCollectionNode->getContext()->getWorkspaceName() === 'live') {
			return $contentCollectionNode->getChildNodes();
		}

		return array_merge($contentCollectionNode->getChildNodes(), $this->getRemovedChildNodes());
	}

	/**
	 * Render the list of nodes, and if there are none and we are not inside the live
	 * workspace, render a button to create new content.
	 *
	 * @return string
	 * @throws \TYPO3\Neos\Exception
	 */
	public function evaluate() {
		$node = $this->getCurrentContextNode();
		$output = parent::evaluate();

		$tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder($this->getTagName());
		$tagBuilder->forceClosingTag(TRUE);
		$tagBuilder->setContent($output);

		$className = 'neos-contentcollection';
		$tagBuilder->addAttribute('class', $className);

		$attributes = $this->tsValue('attributes');
		if (is_array($attributes)) {
			foreach ($attributes as $attributeName => $attributeValue) {
				if (is_array($attributeValue)) {
					$attributeValue = implode(' ', $attributeValue);
				}
				if ($attributeName === 'class') {
					$attributeValue = $tagBuilder->getAttribute('class') . ' ' . $attributeValue;
				}
				$tagBuilder->addAttribute($attributeName, $attributeValue);
			}
		}

		if ($node->getContext()->getWorkspaceName() === 'live' || $this->accessDecisionManager->hasAccessToResource('TYPO3_Neos_Backend_GeneralAccess') === FALSE) {
			return $tagBuilder->render();
		}

		$contentCollectionNode = $this->getContentCollectionNode();

		if ($contentCollectionNode === NULL) {
				// It might still happen that there is no content collection node on the page,
				// f.e. when we are in live workspace. In this case, we just silently
				// return what we have so far.
			return $tagBuilder->render();
		}

		$tagBuilder->addAttribute('about', $contentCollectionNode->getContextPath());
		$tagBuilder->addAttribute('typeof', 'typo3:TYPO3.Neos:ContentCollection');
		$tagBuilder->addAttribute('rel', 'typo3:content-collection');

		$tagBuilder->addAttribute('data-neos-_typoscript-path', $this->path);
		$tagBuilder->addAttribute('data-neos-__workspacename', $contentCollectionNode->getWorkspace()->getName());

		return $tagBuilder->render();
	}

	/**
	 * @return NodeInterface
	 */
	protected function getCurrentContextNode() {
		$currentContext = $this->tsRuntime->getCurrentContext();
		return $currentContext['node'];
	}

	/**
	 * Retrieves the removed nodes for this content collection so the user interface can publish removed nodes as well.
	 *
	 * @return array
	 */
	protected function getRemovedChildNodes() {
		$contentCollectionNode = $this->getContentCollectionNode();
		if ($contentCollectionNode === NULL) {
			return array();
		}

		$contextProperties = $contentCollectionNode->getContext()->getProperties();
		$contextProperties['removedContentShown'] = TRUE;

		$removedNodesContext = $this->contextFactory->create($contextProperties);

		$nodeDataElements = $this->nodeDataRepository->findByParentAndNodeType($contentCollectionNode->getPath(), '', $contentCollectionNode->getContext()->getWorkspace(), NULL, NULL, TRUE);
		$finalNodes = array();
		foreach ($nodeDataElements as $nodeData) {
			$node = $this->nodeFactory->createFromNodeData($nodeData, $removedNodesContext);
			if ($node !== NULL) {
				$finalNodes[] = $node;
			}
		}

		return $finalNodes;
	}
}
