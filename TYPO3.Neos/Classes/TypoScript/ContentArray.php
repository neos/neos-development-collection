<?php
namespace TYPO3\TYPO3\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TYPO3".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\FLOW3\Annotations as FLOW3;

/**
 * A TypoScript Content Array object
 *
 * @FLOW3\Scope("prototype")
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