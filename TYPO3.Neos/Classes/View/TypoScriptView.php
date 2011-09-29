<?php
namespace TYPO3\TYPO3\View;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A view which renders a node based on a TypoScript template
 *
 * @scope prototype
 */
class TypoScriptView extends \TYPO3\FLOW3\MVC\View\AbstractView {

	/**
	 * @inject
	 * @var \TYPO3\TYPO3\Domain\Service\TypoScriptService
	 */
	protected $typoScriptService;

	/**
	 * @inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\NodeRepository
	 */
	protected $nodeRepository;

	/**
	 * @inject
	 * @var \TYPO3\TYPO3CR\Domain\Repository\WorkspaceRepository
	 */
	protected $workspaceRepository;

	/**
	 * @inject
	 * @var \TYPO3\TypoScript\ObjectFactory
	 */
	protected $typoScriptObjectFactory;

	/**
	 * Renders the node assigned to this view, based on the TypoScript configuration
	 * which applies to the current content context.
	 *
	 * @return string Rendered node
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function render() {
		if (!isset($this->variables['value']) || !$this->variables['value'] instanceof \TYPO3\TYPO3CR\Domain\Model\NodeInterface) {
			return 'TypoScriptView: A valid node must be assigned to the variable "value" via the TypoScriptView\'s assign() method.';
		}

		$node = $this->variables['value'];
		$contentContext = $node->getContext();

		$type = 'default';
		$typoScriptObjectTree = $this->typoScriptService->getMergedTypoScriptObjectTree($contentContext->getCurrentSiteNode(), $node);
		if ($typoScriptObjectTree === NULL || count($typoScriptObjectTree) === 0) {
			throw new \TYPO3\TYPO3\Controller\Exception\NoTypoScriptConfigurationException('No TypoScript template was found for the current position in the content tree.', 1255513200);
		}

		$expectedTypoScriptObjectName = $this->typoScriptObjectFactory->getTypoScriptObjectNameByNode($node);

		$firstLevelTypoScriptObject = NULL;
		if ($expectedTypoScriptObjectName === 'TYPO3\TYPO3\TypoScript\Page') {
			foreach ($typoScriptObjectTree as $possibleFirstLevelTypoScriptObject) {
				if (is_a($possibleFirstLevelTypoScriptObject, $expectedTypoScriptObjectName) && $possibleFirstLevelTypoScriptObject->getType() === $type) {
					$firstLevelTypoScriptObject = $possibleFirstLevelTypoScriptObject;
					break;
				}
			}

			if ($firstLevelTypoScriptObject === NULL) {
				throw new \TYPO3\TYPO3\Controller\Exception\NoTypoScriptPageObjectException('No TypoScript Page object with type "' . $type . '" was found in the current TypoScript configuration.', 1255513201);
			}
		} else {
			foreach ($typoScriptObjectTree as $possibleFirstLevelTypoScriptObject) {
				if (is_a($possibleFirstLevelTypoScriptObject, $expectedTypoScriptObjectName)) {
					$firstLevelTypoScriptObject = $possibleFirstLevelTypoScriptObject;
					break;
				}
			}

			if ($firstLevelTypoScriptObject === NULL) {
					// No configured TS Object found, so we use a default one
				$firstLevelTypoScriptObject = new $expectedTypoScriptObjectName();
			}
			$firstLevelTypoScriptObject->setNode($node);
		}

		$renderingContext = new \TYPO3\TypoScript\RenderingContext();
		$renderingContext->setControllerContext($this->controllerContext);
		$renderingContext->setContentContext($contentContext);

		$firstLevelTypoScriptObject->setRenderingContext($renderingContext);
		return $firstLevelTypoScriptObject->render();
	}
}
?>