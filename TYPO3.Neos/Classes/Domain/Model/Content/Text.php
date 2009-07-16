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
 * @version $Id$
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 * @scope prototype
 * @entity
 */
class Text extends \F3\TYPO3\Domain\Model\Content\AbstractContent {

	/**
	 * The text's unique identifier
	 * @var string
	 * @uuid
	 */
	protected $id;

	/**
	 * Headline for this text element
	 * @var string
	 */
	protected $headline = '';

	/**
	 * The text of this text element
	 * @var string
	 */
	protected $text = '';

 	/**
	 * Returns this text's identifier
	 *
	 * @return string The UUID of this text
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getId() {
		return $this->id;
	}

	/**
	 * Returns a label for this Text element
	 *
	 * @return string The label
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function getLabel() {
		return ($this->headline != '') ? $this->headline : '[Untitled]';
	}

}

?>