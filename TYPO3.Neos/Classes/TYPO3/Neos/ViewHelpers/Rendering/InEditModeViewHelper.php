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
use TYPO3\TYPO3CR\Domain\Model\NodeInterface;

/**
 * ViewHelper to find out if Neos is rendering an edit mode.
 *
 * = Examples =
 *
 * Given we are currently in an editing mode:
 *
 * <code title="Basic usage">
 * <f:if condition="{neos:rendering.inEditMode()}">
 *   <f:then>
 *     Shown for editing.
 *   </f:then>
 *   <f:else>
 *     Shown elsewhere (preview mode or not in backend).
 *   </f:else>
 * </f:if>
 * </code>
 * <output>
 * Shown for editing.
 * </output>
 *
 *
 * Given we are in the editing mode named "inPlace"
 *
 * <code title="Advanced usage">
 *
 * <f:if condition="{neos:rendering.inEditMode(mode: 'rawContent')}">
 *   <f:then>
 *     Shown just for rawContent editing mode.
 *   </f:then>
 *   <f:else>
 *     Shown in all other cases.
 *   </f:else>
 * </f:if>
 * </code>
 * <output>
 * Shown in all other cases.
 * </output>
 */
class InEditModeViewHelper extends AbstractRenderingStateViewHelper {

	/**
	 * @param NodeInterface $node Optional Node to use context from
	 * @param string $mode Optional rendering mode name to check if this specific mode is active
	 * @return boolean
	 * @throws \TYPO3\Neos\Exception
	 */
	public function render(NodeInterface $node = NULL, $mode = NULL) {
		$context = $this->getNodeContext($node);
		$renderingMode = $context->getCurrentRenderingMode();
		if ($mode !== NULL) {
			$result = ($renderingMode->getName() === $mode) && $renderingMode->isEdit();
		} else {
			$result = $renderingMode->isEdit();
		}

		return $result;
	}
}