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
class F3_TYPO3_Domain_Model_Page {

	/**
	 * @var F3_TYPO3_Domain_Service_Time
	 * @transient
	 */
	protected $timeService;

	/**
	 * @var string The page title
	 */
	protected $title = '';

	/**
	 * @var If this page is hidden
	 */
	protected $hidden = FALSE;

	/**
	 * @var DateTime A point in time from when this page should be visible
	 */
	protected $startTime;

	/**
	 * @var DateTime A point in time from when this page should be not visible anymore
	 */
	protected $endTime;

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
	public function __construct($title = 'Untitled') {
		$this->setTitle($title);
	}

	/**
	 * Injects the time service
	 *
	 * @param F3_TYPO3_Domain_Service_Time $timeService A reference to the time service
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function injectTimeService(F3_TYPO3_Domain_Service_Time $timeService) {
		$this->timeService = $timeService;
	}

	/**
	 * Hides this page
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function hide() {
		$this->hidden = TRUE;
	}

	/**
	 * Tells if this hidden flag is set for this page.
	 *
	 * If the page is not hidden, this does not automatically mean that it's visible
	 * because other flags affect the visibility of a page. Use isVisible() for determining
	 * if the page is visible.
	 *
	 * @return boolean TRUE if the page is hidden, otherwise FALSE
	 * @see isVisible()
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isHidden() {
		return $this->hidden;
	}

	/**
	 * If this page is visible
	 *
	 * @return boolean TRUE if the page is visible in the frontend, otherwise FALSE
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function isVisible() {
		if ($this->hidden === TRUE) return FALSE;
		$currentTime = $this->timeService->getCurrentDateTime();
		if ($this->startTime !== NULL && ($this->startTime > $currentTime)) return FALSE;
		if ($this->endTime !== NULL && ($this->endTime < $currentTime)) return FALSE;
		return TRUE;
	}

	/**
	 * Sets the point in time from which on this page should be visible.
	 *
	 * @param DateTime $startTime The start time. Passing NULL unsets the start time.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setStartTime(DateTime $startTime) {
		$this->startTime = $startTime;
	}

	/**
	 * Returns the start time if one has been set
	 *
	 * @return DateTime The start time or NULL
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getStartTime() {
		return $this->startTime;
	}

	/**
	 * Sets the point in time from which on this page should be not visible anymore.
	 *
	 * @param DateTime $endTime The end time. Passing NULL unsets the end time.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setEndTime(DateTime $endTime) {
		$this->endTime = $endTime;
	}

	/**
	 * Returns the end time if one has been set
	 *
	 * @return DateTime The end time or NULL
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getEndTime() {
		return $this->endTime;
	}

	/**
	 * Sets the title of this page
	 *
	 * @param string $title The new page title
	 * @return void
	 * @throws InvalidArgumentException if the title is not valid
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setTitle($title) {
		if (!is_string($title)) throw new InvalidArgumentException('The page title must be of type string.', 1175791409);
		if (F3_PHP6_Functions::strlen($title) > 250) throw new InvalidArgumentException('The page title must not exceed 250 characters in length.', 1218199246);

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
	 * @param  F3_TYPO3_Domain_Model_Content $content: The content to add
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addContentElement(F3_TYPO3_Domain_Model_Content $content) {
		$this->contentElements[] = $content;
	}

	/**
	 * Adds a sub page to the page
	 *
	 * @param F3_TYPO3_Domain_Model_Page $page
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function addSubPage(F3_TYPO3_Domain_Model_Page $page) {
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