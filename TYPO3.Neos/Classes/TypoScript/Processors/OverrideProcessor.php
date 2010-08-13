<?php
declare(ENCODING = 'utf-8');
namespace F3\TYPO3\TypoScript\Processors;

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
 * Processor that overrides the current subject with the given value, if the value is not empty.
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class OverrideProcessor implements \F3\TypoScript\ProcessorInterface {

	/**
	 * The value that overrides the subject
	 * @var string
	 */
	protected $replacement = '';

	/**
	 * @param string $replacement The value that overrides the subject
	 * @return void
	 */
	public function setReplacement($replacement) {
		$this->replacement = $replacement;
	}

	/**
	 * @return string The value that overrides the subject
	 */
	public function getReplacement() {
		return $this->replacement;
	}

	/**
	 * Overrides the current subject with the given value, if the value is not empty.
	 *
	 * @param string $subject The string to be processed
	 * @return string The processed string
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function process($subject) {
		$trimmedReplacement = trim((string)$this->replacement);
		$replacementIsEmpty = $trimmedReplacement === '' || $trimmedReplacement === '0';
		return $replacementIsEmpty ? $subject : $this->replacement;
	}
}
?>
