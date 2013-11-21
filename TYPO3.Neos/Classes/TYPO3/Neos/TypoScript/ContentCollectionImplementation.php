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
use TYPO3\TypoScript\TypoScriptObjects\CollectionImplementation;

/**
 * TypoScript object for specific content collections, which also renders a
 * "create-new-content" button when not being in live workspace.
 */
class ContentCollectionImplementation extends CollectionImplementation {

	/**
	 * The name of the content collection node which shall be rendered.
	 *
	 * @var string
	 */
	protected $nodePath;

	/**
	 * Tag name of the tag around the content collection, defaults to "div"
	 *
	 * @var string
	 */
	protected $tagName;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Flow\Security\Authorization\AccessDecisionManagerInterface
	 */
	protected $accessDecisionManager;

	/**
	 * Sets the identifier of the content collection node which shall be rendered
	 *
	 * @param string $nodePath
	 * @return void
	 */
	public function setNodePath($nodePath) {
		$this->nodePath = $nodePath;
	}

	/**
	 * Returns the identifier of the content collection node which shall be rendered
	 *
	 * @return string
	 */
	public function getNodePath() {
		return $this->tsValue('nodePath');
	}

	/**
	 * @param string $tagName
	 */
	public function setTagName($tagName) {
		$this->tagName = $tagName;
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
	 * Render the list of nodes, and if there are none and we are not inside the live
	 * workspace, render a button to create new content.
	 *
	 * @return string
	 * @throws \TYPO3\Neos\Exception
	 */
	public function evaluate() {
		$currentContext = $this->tsRuntime->getCurrentContext();
		$node = $currentContext['node'];
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

		if ($node->getNodeType()->isOfType('TYPO3.Neos:ContentCollection')) {
			$contentCollectionNode = $node;
		} else {
			$contentCollectionNode = $node->getNode($this->getNodePath());
		}

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
}
