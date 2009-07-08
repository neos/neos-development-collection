<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\Domain\Model\Content;

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
 * @package TYPO3
 * @version $Id$
 */

/**
 * Domain model of a page
 *
 * @package TYPO3
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 * @entity
 */
class Page extends \F3\TYPO3\Domain\Model\Content\AbstractCompositeContent implements \F3\TYPO3\Domain\Model\Content\HideableContentInterface {

	/**
	 * The page title
	 * @var string
	 * @validate StringLength(minimum = 1, maximum = 250)
	 */
	protected $title = 'Untitled';

	/**
	 * If this page is hidden
	 * @var boolean
	 */
	protected $hidden = FALSE;

	/**
	 * A point in time from when this page should be visible
	 * @var \DateTime
	 */
	protected $startTime;

	/**
	 * A point in time from when this page should be not visible anymore
	 * @var \DateTime
	 */
	protected $endTime;

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
	 * Unhides this page
	 *
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function unhide() {
		$this->hidden = FALSE;
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
	 * @param \DateTime $startTime The start time. Passing NULL unsets the start time.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setStartTime(\DateTime $startTime) {
		$this->startTime = $startTime;
	}

	/**
	 * Returns the start time if one has been set
	 *
	 * @return \DateTime The start time or NULL
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getStartTime() {
		return $this->startTime;
	}

	/**
	 * Sets the point in time from which on this page should be not visible anymore.
	 *
	 * @param \DateTime $endTime The end time. Passing NULL unsets the end time.
	 * @return void
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setEndTime(\DateTime $endTime) {
		$this->endTime = $endTime;
	}

	/**
	 * Returns the end time if one has been set
	 *
	 * @return \DateTime The end time or NULL
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
	 * @throws \InvalidArgumentException if the title is not valid
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function setTitle($title) {
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
	 * Returns a label for this page
	 *
	 * @return string A label for this page
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getLabel() {
		return $this->title;
	}
}

?>