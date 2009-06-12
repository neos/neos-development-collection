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
 * @subpackage Domain
 * @version $Id$
 */

/**
 * Contract for hideable content
 *
 * @package TYPO3
 * @subpackage Domain
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @author Robert Lemke <robert@typo3.org>
 */
interface HideableContentInterface {

	/**
	 * If this content is visible
	 *
	 * @return boolean TRUE if the content is visible in the frontend, otherwise FALSE
	 */
	public function isVisible();

	/**
	 * Sets the content's state to hidden
	 *
	 * @return void
	 */
	public function hide();

	/**
	 * Sets the content's state to not hidden
	 *
	 * @return void
	 */
	public function unhide();

	/**
	 * Tells if the hidden flag is set for the content.
	 *
	 * If the content is not hidden, it does not imply that it's visible because
	 *other flags affect the visibility of a page. Use isVisible() for determining
	 * if the content is visible.
	 *
	 * @return boolean TRUE if the content is hidden, otherwise FALSE
	 * @see isVisible()
	 */
	public function isHidden();

	/**
	 * Sets the point in time from which on this content should be visible.
	 *
	 * @param \DateTime $startTime The start time. Passing NULL unsets the start time.
	 * @return void
	 */
	public function setStartTime(\DateTime $startTime);

	/**
	 * Returns the start time if one has been set
	 *
	 * @return \DateTime The start time or NULL
	 */
	public function getStartTime();

	/**
	 * Sets the point in time from which on the content should be not visible anymore.
	 *
	 * @param \DateTime $endTime The end time. Passing NULL unsets the end time.
	 * @return void
	 */
	public function setEndTime(\DateTime $endTime);

	/**
	 * Returns the end time if one has been set
	 *
	 * @return \DateTime The end time or NULL
	 */
	public function getEndTime();
}
?>