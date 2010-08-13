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
 * Processor that crops a part of a string and optionally replaces the cropped part by a string,
 * typically three dots ("...").
 *
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License, version 3 or later
 */
class CropProcessor implements \F3\TypoScript\ProcessorInterface {

	const CROP_FROM_BEGINNING = 1;
	const CROP_AT_WORD = 2;
	const CROP_AT_SENTENCE = 4;

	/**
	 * The maximum number of characters to which the subject shall be shortened
	 * @var integer
	 */
	protected $maximumCharacters;

	/**
	 * A string which is to be prepended or appended to the cropped subject if the subject has been cropped at all.
	 * @var string
	 */
	protected $preOrSuffixString = '';

	/**
	 * Any combination of the CROP_ constants as a bitmask
	 * @var long
	 */
	protected $options = 0;

	/**
	 * @param integer $maximumCharacters the maximum number of characters to which the subject shall be shortened
	 * @return void
	 */
	public function setMaximumCharacters($maximumCharacters) {
		$this->maximumCharacters = $maximumCharacters;
	}

	/**
	 * @return integer the maximum number of characters to which the subject shall be shortened
	 */
	public function getMaximumCharacters() {
		return $this->maximumCharacters;
	}

	/**
	 * @param string $preOrSuffixString a string which is to be prepended or appended to the cropped subject if the subject has been cropped at all.
	 * @return void
	 */
	public function setPreOrSuffixString($preOrSuffixString) {
		$this->preOrSuffixString = $preOrSuffixString;
	}

	/**
	 * @return string the string which is to be prepended or appended to the cropped subject if the subject has been cropped at all.
	 */
	public function getPreOrSuffixString() {
		return $this->preOrSuffixString;
	}

	/**
	 * a bitmask combination of the CROP_* constants:
	 * CROP_FROM_BEGINNING: If set, the beginning of the string will be cropped instead of the end.
	 * CROP_AT_WORD: The string will be of the maximum length specified by $maximumCharacters, but it will be cropped after the last (or before the first) space instead of the probably the middle of a word.
	 *
	 * @param long $options any combination of the CROP_ constants as a bitmask
	 * @return void
	 */
	public function setOptions($options) {
		$this->options = $options;
	}

	/**
	 * @return long combination of the CROP_ constants as a bitmask
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * Crops a part of a string and optionally replaces the cropped part by a string, typically
	 * three dots ("...").
	 *
	 * @param string $subject The string to be cropped
	 * @return string The processed string
	 * @author Robert Lemke <robert@typo3.org>
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function process($subject) {
		$processedSubject = $subject;
		if (\F3\FLOW3\Utility\Unicode\Functions::strlen($subject) > $this->maximumCharacters) {
			if ($this->options & self::CROP_FROM_BEGINNING) {
				 if ($this->options & self::CROP_AT_WORD) {
				 	$iterator = new \F3\FLOW3\Utility\Unicode\TextIterator($subject, \F3\FLOW3\Utility\Unicode\TextIterator::WORD);
				 	$processedSubject = \F3\FLOW3\Utility\Unicode\Functions::substr($subject, $iterator->following($this->maximumCharacters));
				 } else {
				 	$processedSubject = \F3\FLOW3\Utility\Unicode\Functions::substr($subject, $this->maximumCharacters);
				 }
				 $processedSubject = $this->preOrSuffixString . $processedSubject;
			} else {
				 if ($this->options & self::CROP_AT_WORD) {
				 	$iterator = new \F3\FLOW3\Utility\Unicode\TextIterator($subject, \F3\FLOW3\Utility\Unicode\TextIterator::WORD);
				 	$processedSubject = \F3\FLOW3\Utility\Unicode\Functions::substr($subject, 0, $iterator->preceding($this->maximumCharacters));
				 } elseif ($this->options & self::CROP_AT_SENTENCE) {
				 	$iterator = new \F3\FLOW3\Utility\Unicode\TextIterator($subject, \F3\FLOW3\Utility\Unicode\TextIterator::SENTENCE);
				 	$processedSubject = \F3\FLOW3\Utility\Unicode\Functions::substr($subject, 0, $iterator->preceding($this->maximumCharacters));
				 } else {
				 	$processedSubject = \F3\FLOW3\Utility\Unicode\Functions::substr($subject, 0, $this->maximumCharacters);
				 }
				 $processedSubject .= $this->preOrSuffixString;
			}
		}
		return $processedSubject;
	}
}
?>
