<?php
namespace TYPO3\Neos\View;

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
 * Controller for displaying nodes in the frontend
 *
 */
class TypoScriptView extends \TYPO3\Flow\Mvc\View\AbstractView {

	/**
	 * @Flow\Inject
	 * @var \TYPO3\Neos\Domain\Service\TypoScriptService
	 */
	protected $typoScriptService;

	/**
	 * The TypoScript path to use for rendering the node given in "value", defaults to "page".
	 *
	 * @var string
	 */
	protected $typoScriptPath = 'root';

	/**
	 * Renders the view
	 *
	 * @return string The rendered view
	 * @throws \TYPO3\Neos\Exception if no node is given
	 * @api
	 */
	public function render() {
		$currentNode = isset($this->variables['value']) ? $this->variables['value'] : NULL;
		if (!$currentNode instanceof \TYPO3\TYPO3CR\Domain\Model\Node) {
			throw new \TYPO3\Neos\Exception('TypoScriptView needs a node as argument.', 1329736456);
		}

			// TODO: find closest document node from this node...
		$closestDocumentNode = $currentNode;
		$currentSiteNode = $currentNode->getContext()->getCurrentSiteNode();

		$typoScriptRuntime = $this->typoScriptService->createRuntime($currentSiteNode, $closestDocumentNode, $this->controllerContext);

		$typoScriptRuntime->pushContextArray(array(
			'node' => $currentNode,
			'documentNode' => $closestDocumentNode,
			'request' => $this->controllerContext->getRequest(),
			'site' => $currentSiteNode
		));
		$output = $typoScriptRuntime->render($this->typoScriptPath);
		$typoScriptRuntime->popContext();

		return $output;
	}

	/**
	 * Is it possible to render $node with $typoScriptPath?
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\Node $node
	 * @param string $typoScriptPath
	 * @return boolean TRUE if $node can be rendered at $typoScriptPath
	 */
	public function canRenderWithNodeAndPath(\TYPO3\TYPO3CR\Domain\Model\Node $node, $typoScriptPath) {
			// TODO: find closest document node from this node...
		$closestDocumentNode = $node;
		$currentSiteNode = $node->getContext()->getCurrentSiteNode();

		$typoScriptRuntime = $this->typoScriptService->createRuntime($currentSiteNode, $closestDocumentNode, $this->controllerContext);
		return $typoScriptRuntime->canRender($typoScriptPath);
	}

	/**
	 * Set the TypoScript path to use for rendering the node given in "value"
	 *
	 * @param string $typoScriptPath
	 * @return void
	 */
	public function setTypoScriptPath($typoScriptPath) {
		$this->typoScriptPath = $typoScriptPath;
	}

	/**
	 * @return string
	 */
	public function getTypoScriptPath() {
		return $this->typoScriptPath;
	}
}
