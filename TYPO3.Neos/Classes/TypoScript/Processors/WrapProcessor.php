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
 * Processor that wraps the specified string into a prefix- and a suffix string.
 *
 */
class WrapProcessor implements \TYPO3\TypoScript\ProcessorInterface {

	/**
	 * The string to prepend
	 * @var string
	 */
	protected $prefix = '';

	/**
	 * The string to append
	 * @var string
	 */
	protected $suffix = '';

	/**
	 * @param string $prefix a string to be prepended
	 * @return void
	 */
	public function setPrefix($prefix) {
		$this->prefix = $prefix;
	}

	/**
	 * @return string the string which is to be prepended to the subject
	 */
	public function getPrefix() {
		return $this->prefix;
	}

	/**
	 * @param string $suffix a string to be appended
	 * @return void
	 */
	public function setSuffix($suffix) {
		$this->suffix = $suffix;
	}

	/**
	 * @return string the string which is to be appended to the subject
	 */
	public function getSuffix() {
		return $this->suffix;
	}

	/**
	 * Wraps the specified string into a prefix- and a suffix string.
	 *
	 * @param string $subject the string to be wrapped
	 * @return string The processed string
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function process($subject) {
		return $this->prefix . $subject . $this->suffix;
	}
}
?>
