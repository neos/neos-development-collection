<?php
namespace TYPO3\TypoScript\Processors;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Crops a part of a string and optionally replaces the cropped part by a string.
 *
 */
class CropProcessor implements \TYPO3\TypoScript\ProcessorInterface {

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
	 * @var integer
	 */
	protected $options = 0;

	/**
	 * The maximum number of characters to which the subject shall be shortened.
	 *
	 * @param integer $maximumCharacters
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
	 * The string which is to be prepended or appended to the cropped
	 * subject if the subject has been cropped at all.
	 *
	 * @param string $preOrSuffixString
	 * @return void
	 */
	public function setPreOrSuffixString($preOrSuffixString) {
		$this->preOrSuffixString = $preOrSuffixString;
	}

	/**
	 * @return string to be prepended or appended to the cropped subject
	 */
	public function getPreOrSuffixString() {
		return $this->preOrSuffixString;
	}

	/**
	 * A bitmask combination of the CROP_* constants:
	 *
	 * * CROP_FROM_BEGINNING: If set, the beginning of the string will be cropped instead of the end.
	 * * CROP_AT_WORD: The string will be of the maximum length specified by $maximumCharacters, but it will be cropped after a word instead of probably the middle of a word.
	 * * CROP_AT_SENTENCE: The string will be of the maximum length specified by $maximumCharacters, but it will be cropped after a sentence instead of probably the middle of a word.
	 *
	 * @param integer $options any combination of the CROP_ constants as a bitmask
	 * @return void
	 */
	public function setOptions($options) {
		$this->options = $options;
	}

	/**
	 * @return integer Combination of the CROP_ constants as a bitmask
	 */
	public function getOptions() {
		return $this->options;
	}

	/**
	 * Crops a part of a string and optionally replaces the cropped part by a string.
	 *
	 * @param string $subject The string to be cropped
	 * @return string The processed string
	 */
	public function process($subject) {
		$processedSubject = $subject;
		if (\TYPO3\Flow\Utility\Unicode\Functions::strlen($subject) > $this->maximumCharacters) {
			if ($this->options & self::CROP_FROM_BEGINNING) {
				if ($this->options & self::CROP_AT_WORD) {
					$iterator = new \TYPO3\Flow\Utility\Unicode\TextIterator($subject, \TYPO3\Flow\Utility\Unicode\TextIterator::WORD);
					$processedSubject = \TYPO3\Flow\Utility\Unicode\Functions::substr($subject, $iterator->following($this->maximumCharacters));
				} else {
					$processedSubject = \TYPO3\Flow\Utility\Unicode\Functions::substr($subject, $this->maximumCharacters);
				}
				$processedSubject = $this->preOrSuffixString . $processedSubject;
			} else {
				if ($this->options & self::CROP_AT_WORD) {
					$iterator = new \TYPO3\Flow\Utility\Unicode\TextIterator($subject, \TYPO3\Flow\Utility\Unicode\TextIterator::WORD);
					$processedSubject = \TYPO3\Flow\Utility\Unicode\Functions::substr($subject, 0, $iterator->preceding($this->maximumCharacters));
				} elseif ($this->options & self::CROP_AT_SENTENCE) {
					$iterator = new \TYPO3\Flow\Utility\Unicode\TextIterator($subject, \TYPO3\Flow\Utility\Unicode\TextIterator::SENTENCE);
					$processedSubject = \TYPO3\Flow\Utility\Unicode\Functions::substr($subject, 0, $iterator->preceding($this->maximumCharacters));
				} else {
					$processedSubject = \TYPO3\Flow\Utility\Unicode\Functions::substr($subject, 0, $this->maximumCharacters);
				}
				$processedSubject .= $this->preOrSuffixString;
			}
		}
		return $processedSubject;
	}
}
?>