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
	 * additional CSS classes to be applied to the content collection
	 *
	 * @var string
	 */
	protected $class;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeDataRepository
	 */
	protected $nodeDataRepository;

	/**
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Service\NodeTypeManager
	 */
	protected $nodeTypeManager;

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
	 * additional CSS classes to be applied to the content collection
	 *
	 * @param string $class
	 * @return void
	 */
	public function setClass($class) {
		$this->class = $class;
	}

	/**
	 * additional CSS classes to be applied to the content collection
	 *
	 * @return string
	 */
	public function getClass() {
		return $this->tsValue('class');
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
		try {
			$this->accessDecisionManager->decideOnResource('TYPO3_Neos_Backend_GeneralAccess');
		} catch (\TYPO3\Flow\Security\Exception\AccessDeniedException $e) {
			return $output;
		}

		if ($node->getNodeType()->isOfType('TYPO3.Neos:ContentCollection')) {
			$contentCollectionNode = $node;
		} else {
			$contentCollectionNode = $node->getNode($this->getNodePath());

			if ($contentCollectionNode === NULL && $node->getContext()->getWorkspaceName() !== 'live') {
					/**
					 * In case the user created a new page, this page does not have the necessary content collections created yet.
					 * The problem is that we only know during TypoScript rendering which content collections we expect to have
					 * on a certain page; as it is only stored in the "nodePath" property of this ContentCollection TypoScript object.
					 *
					 * Thus, as a workaround, we create new content collection nodes as we need them during rendering, although we
					 * know it is ugly.
					 */
				$contentCollectionNode = $node->createNode($this->getNodePath(), $this->nodeTypeManager->getNodeType('TYPO3.Neos:ContentCollection'));
				$this->nodeDataRepository->persistEntities();
			}
		}

		if ($contentCollectionNode === NULL) {
				// It might still happen that there is no content collection node on the page,
				// f.e. when we are in live workspace. In this case, we just silently
				// return what we have so far.
			return $output;
		}

		$tagBuilder = new \TYPO3\Fluid\Core\ViewHelper\TagBuilder('div');

		$class = 'neos-contentcollection';
		$additionalClass = $this->tsValue('class');
		if ($additionalClass) {
			$class .= ' ' . $additionalClass;
		}

		$tagBuilder->addAttribute('about', $contentCollectionNode->getContextPath());
		$tagBuilder->addAttribute('typeof', 'typo3:TYPO3.Neos:ContentCollection');
		$tagBuilder->addAttribute('rel', 'typo3:content-collection');
		$tagBuilder->addAttribute('class', $class);
		$tagBuilder->addAttribute('data-neos-_typoscript-path', $this->path);
		$tagBuilder->addAttribute('data-neos-__workspacename', $contentCollectionNode->getWorkspace()->getName());
		$tagBuilder->setContent($output);

		return $tagBuilder->render();
	}
}
?>
