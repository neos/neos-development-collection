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
 * @subpackage Service
 * @version $Id:F3_TYPO3_View_Page.php 262 2007-07-13 10:51:44Z robert $
 */

/**
 *
 *
 * @package TYPO3
 * @subpackage Service
 * @version $Id:F3_TYPO3_View_Page.php 262 2007-07-13 10:51:44Z robert $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3_Service_View_Pages_ListJSON extends F3_FLOW3_MVC_View_AbstractView {

	/**
	 * @var array An array of pages
	 */
	protected $pages;

	/**
	 * Sets the pages (model) for this view
	 *
	 * @param array $pages An array of F3_TYPO3_Domain_Model_Page objects
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setPages(array $pages) {
		$this->pages = $pages;
	}

	/**
	 * Renders a page from the given TypoScript
	 *
	 * @param  array $typoScriptObjectTree: The TypoScript tree (model)
	 * @return string The rendered content
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function render() {
		$pagesArray = array();
		foreach ($this->pages as $key => $page) {
			$pagesArray[] = array(
				'text' => $page->getTitle()
			);
		}
		return json_encode($pagesArray);
	}
}
?>