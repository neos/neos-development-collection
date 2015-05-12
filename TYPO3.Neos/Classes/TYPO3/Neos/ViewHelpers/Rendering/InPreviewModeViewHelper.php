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
 * ViewHelper to find out if Neos is rendering a preview mode.
 *
 * = Examples =
 *
 * Given we are currently in a preview mode:
 *
 * <code title="Basic usage">
 * <f:if condition="{neos:rendering.inPreviewMode()}">
 *   <f:then>
 *     Shown in preview.
 *   </f:then>
 *   <f:else>
 *     Shown elsewhere (edit mode or not in backend).
 *   </f:else>
 * </f:if>
 * </code>
 * <output>
 * Shown in preview.
 * </output>
 *
 *
 * Given we are in the preview mode named "desktop"
 *
 * <code title="Advanced usage">
 *
 * <f:if condition="{neos:rendering.inPreviewMode(mode: 'print')}">
 *   <f:then>
 *     Shown just for print preview mode.
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
class InPreviewModeViewHelper extends AbstractRenderingStateViewHelper {

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
			$result = ($renderingMode->getName() === $mode) && $renderingMode->isPreview();
		} else {
			$result = $renderingMode->isPreview();
		}

		return $result;
	}
}