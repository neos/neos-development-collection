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
 * @version $Id$
 */

/**
 * Domain model of a page
 *
 * @package TYPO3
 * @version $Id$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 * @entity
 */
class F3_TYPO3_Domain_Page {

	/**
	 * @var string The page title
	 */
	protected $title;

	/**
	 * @var array Content elements on this page
	 */
	protected $contentElements = array();

	/**
	 * @var array Sub pages of this page
	 */
	protected $subPages = array();

	/**
	 * Constructs the Page
	 *
	 * @param string $title The page title
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($title) {
		$this->setTitle($title);
	}

	/**
	 * Sets the title of this page
	 *
	 * @param string $title The new page title
	 * @return Robert Lemke <robert@typo3.org>
	 */
	public function setTitle($title) {
		if (!is_string($title)) throw new InvalidArgumentException('The page title must be of type string.', 1175791409);
		$this->title = $title;
	}

	/**
	 * Returns the page's title
	 *
	 * @return string The title of the page
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Adds content to the page
	 *
	 * @param  F3_TYPO3_Domain_Content $content: The content to add
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addContentElement(F3_TYPO3_Domain_Content $content) {
		$this->contentElements[] = $content;
	}

	/**
	 * Adds a sub page to the page
	 *
	 * @param F3_TYPO3_Domain_Page $page
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addSubPage(F3_TYPO3_Domain_Page $page) {
		$this->subPages[] = $page;
	}

	/**
	 * Cloning of a page is not allowed
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __clone() {
		throw new LogicException('Cloning of a Page is not allowed.', 1175793217);
	}
}

?>