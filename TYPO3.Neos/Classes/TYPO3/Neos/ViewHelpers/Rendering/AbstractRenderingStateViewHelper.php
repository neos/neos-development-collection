<?php
namespace TYPO3\Neos\ViewHelpers\Rendering;

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
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\Fluid\Core\ViewHelper\Exception as ViewHelperException;
use TYPO3\Neos\Domain\Service\ContentContext;
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;
use TYPO3\TypoScript\TypoScriptObjects\Helpers\TypoScriptAwareViewInterface;

/**
 * Abstract ViewHelper for all Neos rendering state helpers.
 */
abstract class AbstractRenderingStateViewHelper extends AbstractViewHelper {

	/**
	 * Get a node from the current TypoScript context if available.
	 *
	 * @return NodeInterface|NULL
	 *
	 * @TODO Refactor to a TypoScript Context trait (in TYPO3.TypoScript) that can be used inside ViewHelpers to get variables from the TypoScript context.
	 */
	protected function getContextNode() {
		$baseNode = NULL;
		$view = $this->viewHelperVariableContainer->getView();
		if ($view instanceof TypoScriptAwareViewInterface) {
			$typoScriptObject = $view->getTypoScriptObject();
			$currentContext = $typoScriptObject->getTsRuntime()->getCurrentContext();
			if (isset($currentContext['node'])) {
				$baseNode = $currentContext['node'];
			}
		}

		return $baseNode;
	}

	/**
	 * @param NodeInterface $node
	 * @return ContentContext
	 * @throws ViewHelperException
	 */
	protected function getNodeContext(NodeInterface $node = NULL) {
		if ($node === NULL) {
			$node = $this->getContextNode();
			if ($node === NULL) {
				throw new ViewHelperException('The ' . get_class($this) . ' needs a Node to determine the state. We could not find one in your context so please provide it as "node" argument to the ViewHelper.', 1427267133);
			}
		}

		$context = $node->getContext();
		if (!$context instanceof ContentContext) {
			throw new ViewHelperException('Rendering state can only be obtained with Nodes that are in a Neos ContentContext. Please provide a Node with such a context.', 1427720037);
		}

		return $context;
	}
}