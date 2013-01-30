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
use TYPO3\TypoScript\Core\Runtime;

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
	 * @Flow\Inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

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
		if (!$currentNode instanceof \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface) {
			throw new \TYPO3\Neos\Exception('TypoScriptView needs a persisted node as argument.', 1329736456);
		}

			// TODO: find closest folder node from this node...
		$closestFolderNode = $currentNode;
		$currentSiteNode = $this->nodeRepository->getContext()->getCurrentSiteNode();
		$typoScriptObjectTree = $this->typoScriptService->getMergedTypoScriptObjectTree($currentSiteNode, $closestFolderNode);

		$typoScriptRuntime = new Runtime($typoScriptObjectTree, $this->controllerContext);
		$typoScriptRuntime->pushContextArray(array(
			'node' => $currentNode,
			'request' => $this->controllerContext->getRequest()
		));
		$output = $typoScriptRuntime->render($this->typoScriptPath);
		$typoScriptRuntime->popContext();

		return $output;
	}

	/**
	 * Is it possile to render $node with $typoScriptPath?
	 *
	 * @param \TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node
	 * @param string $typoScriptPath
	 * @return boolean TRUE if $node can be rendered at $typoScriptPath
	 */
	public function canRenderWithNodeAndPath(\TYPO3\TYPO3CR\Domain\Model\PersistentNodeInterface $node, $typoScriptPath) {
		$currentSiteNode = $this->nodeRepository->getContext()->getCurrentSiteNode();

		// TODO: find closest folder node from this node...
		$closestFolderNode = $node;
		$typoScriptConfiguration = $this->typoScriptService->getMergedTypoScriptObjectTree($currentSiteNode, $closestFolderNode);
		$typoScriptRuntime = new \TYPO3\TypoScript\Core\Runtime($typoScriptConfiguration, $this->controllerContext);
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

?>
