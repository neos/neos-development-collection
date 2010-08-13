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
 * Domain model of a Text content element
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 * @entity
 * @api
 */
class Block extends \F3\TYPO3\Domain\Model\Content\AbstractContent {

	/**
	 * The headline of this block element
	 * @var string
	 * @validate String
	 */
	protected $headline = '';

	/**
	 * The HTML of this block element
	 * @var string
	 * @validate String
	 */
	protected $html = '';

	/**
	 * Get the headline of this block element
	 *
	 * @return string
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function getHeadline() {
		return $this->headline;
	}

	/**
	 * Set the headline for this block element
	 *
	 * @param string $headline
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function setHeadline($headline) {
		$this->headline = $headline;
	}

	/**
	 * @return string the HTML
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function getHtml() {
		return $this->html;
	}

	/**
	 * @param string $html the HTML
	 * @return void
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 * @api
	 */
	public function setHtml($html) {
		$this->html = $html;
	}
}

?>