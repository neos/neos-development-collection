<?php
namespace TYPO3\TYPO3\ViewHelpers;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
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
	 */
	public function render(\TYPO3\TYPO3CR\Domain\Model\NodeInterface $node) {
		return $this->contentElementWrappingService->wrapContentObject($node, $this->renderChildren());
	}
}
?>