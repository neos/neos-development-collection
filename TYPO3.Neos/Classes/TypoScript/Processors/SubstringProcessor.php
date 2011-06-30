<?php
namespace TYPO3\TYPO3\TypoScript\Processors;

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
 * Processor that returns a substring of the given subject
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class SubstringProcessor implements \TYPO3\TypoScript\ProcessorInterface {

	/**
	 * The left boundary of the substring
	 * @var integer
	 */
	protected $start = 0;

	/**
	 * The length of the substring
	 * @var integer
	 */
	protected $length = NULL;

	/**
	 * @param integer $start the left boundary of the substring
	 * @return void
	 */
	public function setStart($start) {
		$this->start = $start;
	}

	/**
	 * @return integer the left boundary of the substring
	 */
	public function getStart() {
		return $this->start;
	}

	/**
	 * @param integer $length the length of the substring
	 * @return void
	 */
	public function setLength($length) {
		$this->length = $length;
	}

	/**
	 * @return integer the length of the substring
	 */
	public function getLength() {
		return $this->length;
	}

	/**
	 * Returns a substring of the specified subject
	 *
	 * @param string $subject The string to be processed
	 * @return string The processed string
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function process($subject) {
		if (!is_integer($this->start)) throw new \TYPO3\TypoScript\Exception('Expected an integer as start position, ' . gettype($this->start) . ' given.', 1224003810);
		if ($this->length !== NULL && !is_integer($this->length)) throw new \TYPO3\TypoScript\Exception('Expected an integer as length, ' . gettype($this->length) . ' given.', 1224003811);

		return \TYPO3\FLOW3\Utility\Unicode\Functions::substr((string)$subject, $this->start, $this->length);
	}
}
?>
