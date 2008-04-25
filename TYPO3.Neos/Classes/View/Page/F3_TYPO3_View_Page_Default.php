<?php
declare(ENCODING = 'utf-8');

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

/**
 * @package TYPO3
 * @version $Id:F3_TYPO3_View_Page.php 262 2007-07-13 10:51:44Z robert $
 * @copyright Copyright belongs to the respective authorst
 */

/**
 * TypoScript View for a Page
 *
 * @package TYPO3
 * @version $Id:F3_TYPO3_View_Page.php 262 2007-07-13 10:51:44Z robert $
 * @copyright Copyright belongs to the respective authorst
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3_View_Page_Default extends F3_FLOW3_MVC_View_Abstract {

	/**
	 * @var array
	 */
	public $typoScriptObjectTree;

	/**
	 * Renders a page from the given TypoScript
	 *
	 * @param  array $typoScriptObjectTree: The TypoScript tree (model)
	 * @return string The rendered content
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function render() {
		foreach ($this->typoScriptObjectTree as $name => $typoScriptObject) {
			if ($typoScriptObject instanceof F3_TYPO3_TypoScript_Page) {
				$typoScriptPageObjectName = $name;
			}
		}
		if (!isset($typoScriptPageObjectName)) {
			return 'Error: No TypoScript Page object has been defined for this view.';
		} else {
			return $this->typoScriptObjectTree[$typoScriptPageObjectName]->getRenderedContent();
		}
	}
}
?>