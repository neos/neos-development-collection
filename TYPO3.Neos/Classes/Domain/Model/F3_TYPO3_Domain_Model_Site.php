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
 * @subpackage Domain
 * @version $Id:$
 */

/**
 * Domain model of a site
 *
 * @package TYPO3
 * @subpackage Domain
 * @version $Id:$
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 * @scope prototype
 * @entity
 */
class F3_TYPO3_Domain_Model_Site {

	/**
	 * @var string The site's unique identifier
	 * @identifier
	 */
	protected $identifier;

	/**
	 * @var string
	 */
	protected $name = 'Untitled Site';

	/**
	 * @var array Pages on the first level of the site
	 */
	protected $pages = array();

	/**
	 * Constructs the new site
	 *
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function __construct() {

	}

	/**
	 * Sets the name for this site
	 *
	 * @param string $name The site name
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * Returns the name of this site
	 *
	 * @return string The name
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getName() {
		return $this->name;
	}

	/**
	 * Adds a page to the first level of the website
	 *
	 * @param F3_TYPO3_Domain_Model_Page $page The page to add
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addPage(F3_TYPO3_Domain_Model_Page $page) {
		 $this->pages[] = $page;
	}

	/**
	 * Returns the first page of the website.
	 *
	 * @return F3_TYPO3_Domain_Model_Page The root page - or NULL if no root page exists
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getRootPage() {
		return (array_key_exists(0, $this->pages)) ? $this->pages[0] : NULL;
	}
}

?>