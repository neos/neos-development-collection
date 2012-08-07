<?php
namespace TYPO3\TYPO3\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * Renderer for specific sections, which also renders a "create-new-content" button
 * when not being in live workspace.
 */
class Section extends \TYPO3\TypoScript\TypoScriptObjects\CollectionRenderer {

	/**
	 * The identifier of the section Node which shall be rendered.
	 *
	 * @var string
	 */
	protected $nodePath;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @FLOW3\Inject
	 * @var \TYPO3\FLOW3\Security\Authorization\AccessDecisionManagerInterface
	 */
	protected $accessDecisionManager;

	/**
	 * @return string the identifier of the section node which shall be rendered
	 */
	public function getNodePath() {
		return $this->tsValue('nodePath');
	}

	/**
	 * @param string $nodePath the identifier of the section node which shall be rendered
	 */
	public function setNodePath($nodePath) {
		$this->nodePath = $nodePath;
	}

	/**
	 * Render the list of nodes, and if there are none and we are not inside the live
	 * workspace, render a button to create new content.
	 *
	 * @return string
	 */
	public function evaluate() {
		$currentContext = $this->tsRuntime->getCurrentContext();
		$node = $currentContext['node'];
		$output = parent::evaluate();
		try {
			$this->accessDecisionManager->decideOnResource('TYPO3_TYPO3_Backend_BackendController');
		} catch (\TYPO3\FLOW3\Security\Exception\AccessDeniedException $e) {
			return $output;
		}

		if ($this->numberOfRenderedNodes === 0 && $this->nodeRepository->getContext()->getWorkspaceName() !== 'live') {
			$sectionNode = $node->getNode($this->getNodePath());
			if ($sectionNode === NULL) {
				$sectionNode = $node->createNode($this->getNodePath(), 'TYPO3.TYPO3:Section');
			}
			$output = '<div class="t3-ui"><button class="t3-create-new-content t3-button btn" data-node="' . $sectionNode->getContextPath() . '">Create new content</button></div>';
		}

		return $output;
	}
}
?>