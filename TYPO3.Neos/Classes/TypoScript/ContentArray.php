<?php
namespace TYPO3\TYPO3\TypoScript;

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
 * A TypoScript Content Array object
 *
 * @scope prototype
 */
class ContentArray extends \TYPO3\TypoScript\AbstractContentArrayObject {

	public function render() {
			// TODO: add check to make sure user is logged in when he accesses that workspace.
		if ($this->count() === 0 && $this->renderingContext->getContentContext()->getWorkspaceName() !== 'live') {
			return '<button class="t3-create-new-content t3-button" data-node="' . $this->node->getContextPath() . '"><span>Create new content</span></button>';
		} else {
			return parent::render();
		}
	}
}
?>