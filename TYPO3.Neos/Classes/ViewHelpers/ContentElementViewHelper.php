<?php
namespace TYPO3\TYPO3\ViewHelpers;

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
 * ViewHelper which wraps all content elements, and adds an additional div wrapper
 * if we are in backend mode.
 *
 * @scope singleton
 */
class ContentElementViewHelper extends \TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @inject
	 * @var \TYPO3\TYPO3\Service\ContentElementWrappingService
	 */
	protected $contentElementWrappingService;

	/**
	 * Include all JavaScript files matching the include regular expression
	 * and not matching the exclude regular expression.
	 *
	 * @param TYPO3\TYPO3CR\Domain\Model\NodeInterface $node
	 * @param boolean $page
	 */
	public function render(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node, $page = FALSE) {
		return $this->contentElementWrappingService->wrapContentObject($node, $this->renderChildren(), $page);
	}
}
?>