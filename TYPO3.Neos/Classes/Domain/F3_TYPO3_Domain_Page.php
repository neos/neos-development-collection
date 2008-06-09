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
 * A Page
 *
 * @package		CMS
 * @version 	$Id$
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 *
 * @scope prototype
 */
class F3_TYPO3_Domain_Page {

	/**
	 * @var string The UUID of this page
	 */
	protected $uuid;

	/**
	 * @var string The page title
	 */
	protected $title;

	/**
	 * @var boolean Flags if the page is hidden
	 */
	protected $hidden = FALSE;

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
	 * @param  string			$title: The page title
	 * @param  F3_FLOW3_Utility_Algorithms $utilityAlgorithms: A reference to the algorithms utility component
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct($title) {
		$this->uuid = F3_FLOW3_Utility_Algorithms::generateUUID();
		$this->setTitle($title);
	}

	/**
	 * Returns the page's universially unique identifier
	 *
	 * @return string			The UUID of the page
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getUUID() {
		return $this->uuid;
	}

	/**
	 * Sets the title of this page
	 *
	 * @param  string			$title: The new page title
	 * @return Robert Lemke <robert@typo3.org>
	 */
	public function setTitle($title) {
		if (!is_string($title)) throw new InvalidArgumentException('The page title must be of type string.', 1175791409);
		$this->title = $title;
	}

	/**
	 * Returns the page's title
	 *
	 * @return string			The title of the page
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Sets the "hidden" flag of the page
	 *
	 * @param  boolean			$hidden: If the page is hidden
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setHidden($hidden) {
		if (!is_bool($hidden)) throw new InvalidArgumentException('The hidden flag must be of type boolean.', 1175791401);
		$this->hidden = $hidden;
	}

	/**
	 * Sets multiple properties from the keys and values taken from
	 * the specified array.
	 *
	 * @param  array			$array: An array of keys and values of the properties to set
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setFromArray(array $array) {
		foreach ($array as $key => $value) {
			$methodName = 'set' . ucfirst($key);
			if (!method_exists($this, $methodName)) throw new InvalidArgumentException('Cannot set the property "' . $key . '" in class ' . get_class($this) . ' because it doesn\'t exist.', 1176793136);
			$this->$methodName($value);
		}
	}

	/**
	 * Returns the "hidden" flag of the page
	 *
	 * @return boolean			If the page is hidden
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isHidden() {
		return $this->hidden;
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
	 * @param  F3_TYPO3_Domain_Page $page
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