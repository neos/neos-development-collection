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
 * A library of standard processors for TypoScript objects. Most of these functions
 * were known as "standard wrap" properties / functions in TYPO3 4.x.
 * 
 * @package		CMS
 * @version 	$Id$
 * @copyright	Copyright belongs to the respective authors
 * @license		http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class F3_TYPO3_TypoScript_Processors {
	
	const CROP_FROM_BEGINNING = 1;
	const CROP_AT_WORD = 2;
	const CROP_AT_SENTENCE = 4;
	
	const SHIFT_CASE_TO_UPPER = 1;
	const SHIFT_CASE_TO_LOWER = 2;
	const SHIFT_CASE_TO_TITLE = 4;
	
	/**
	 * Crops a part of a string and optionally replaces the cropped part by a string, typically
	 * three dots ("...").
	 * 
	 * The third parameter is a bitmask combination of the CROP_* constants:
	 * 
	 *    CROP_FROM_BEGINNING:		If set, the beginning of the string will be cropped instead of the end.
	 *    CROP_AT_WORD:				The string will be of the maximum length specified by $maximumNumberOfCharacters, but it will be cropped after the last (or before the first) space instead of the probably the middle of a word.
	 * 
	 * @param  string				$subject: The string to crop
	 * @param  integer				$maximumNumberOfCharacters: The maximum number of characters to which the subject shall be shortened
	 * @param  string				$preOrSuffixString: A string which is to be prepended or appended to the cropped subject if the subject has been cropped at all.
	 * @param  long					$options: Any combination of the CROP_ constants as a bitmask
	 * @return string				The processed string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processor_crop($subject, $maximumNumberOfCharacters, $preOrSuffixString = '', $options = 0) {
		if (F3_PHP6_Functions::strlen($subject) > $maximumNumberOfCharacters) {
			if ($options & self::CROP_FROM_BEGINNING) {				
				if ($options & self::CROP_AT_WORD) {
					$iterator = new F3_PHP6_TextIterator($subject, F3_PHP6_TextIterator::WORD);					
					$processedSubject = F3_PHP6_Functions::substr($subject, $iterator->following($maximumNumberOfCharacters));
				} else {
					$processedSubject = F3_PHP6_Functions::substr($subject, $maximumNumberOfCharacters);
				}
				$processedSubject = $preOrSuffixString . $processedSubject;
			} else {
				if ($options & self::CROP_AT_WORD) {
					$iterator = new F3_PHP6_TextIterator($subject, F3_PHP6_TextIterator::WORD);					
					$processedSubject = F3_PHP6_Functions::substr($subject, 0, $iterator->preceding($maximumNumberOfCharacters));
				} elseif ($options & self::CROP_AT_SENTENCE) {
					$iterator = new F3_PHP6_TextIterator($subject, F3_PHP6_TextIterator::SENTENCE);					
					$processedSubject = F3_PHP6_Functions::substr($subject, 0, $iterator->preceding($maximumNumberOfCharacters));				
				} else {
					$processedSubject = F3_PHP6_Functions::substr($subject, 0, $maximumNumberOfCharacters);					
				}
				$processedSubject .= $preOrSuffixString;
			}
		}
		return $processedSubject;
	}
	
	/**
	 * Wraps the specified string into a prefix- and a suffix string.
	 *
	 * @param  string				$subject: The string to wrap
	 * @param  string				$prefixString: The string to prepend
	 * @param  string				$suffixString: The string to append
	 * @return string				The processed string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processor_wrap($subject, $prefixString, $suffixString) {
		return $prefixString . $subject . $suffixString;
	}
	
	/**
	 * Shifts the case of a string into the specified direction.
	 *
	 * @param  string				$subject: The string to change the case for
	 * @param  long					$direction: One of the SHIFT_CASE_* constants
	 * @return string				The processed string
	 * @author Robert Lemke <robert@typo3.org>
	 */
	public function processor_shiftCase($subject, $direction) {		
		switch ($direction) {
			case self::SHIFT_CASE_TO_LOWER :
				$processedSubject = F3_PHP6_Functions::strtolower($subject);
				break;
			case self::SHIFT_CASE_TO_UPPER :
				$processedSubject = F3_PHP6_Functions::strtoupper($subject);
				break;
			case self::SHIFT_CASE_TO_TITLE :
				$processedSubject = F3_PHP6_Functions::strtotitle($subject);
				break;
			default:
				throw new F3_TypoScript_Exception('Invalid direction specified for case shift. Please use one of the SHIFT_CASE_* constants.', 1179399480);
		}
		return $processedSubject;
	}
	
	/**
	 * Transforms an UNIX timestamp according to the given format. For the possible format values, look at the php date() function.
	 * 
	 * @param  string				$subject: The UNIX timestamp to transform
	 * @param  long					$format: A format string, according to the rules of the php date() function
	 * @return string				The transformed date string
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function processor_date($subject, $format) {
		if ($subject === '') return '';

		$timestamp = is_object($subject) ? (string)$subject : $subject;
		$format = (string)$format;
		if($timestamp <= 0) throw new F3_TypoScript_Exception('The given timestamp value was zero or negative, sorry this is not allowed.', 1185282371);
		
		return date($format, $timestamp);
	}
	
	/**
	 * Overrides the current subject with the given value, if the value is not empty.
	 * 
	 * @param  string				$subject: The current subject in the processor chain
	 * @param  string				$replacement: The value that overrides the subject
	 * @return string				The new subject
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function processor_override($subject, $replacement) {
		$replacementIsEmpty = (trim((string)$replacement) === '' || trim((string)$replacement) === '0');		
		return $replacementIsEmpty ? $subject : $replacement;		
	}
	
	/**
	 * Overrides the current subject with the given value, if the subject (trimmed) is empty.
	 * 
	 * @param  string				$subject: The current subject in the processor chain
	 * @param  string				$replacement: The value that overrides the subject
	 * @return string				The new subject
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function processor_ifEmpty($subject, $replacement) {
		$subjectIsEmpty = (trim((string)$subject) === '' || trim((string)$subject) === '0');		
		return $subjectIsEmpty ? $replacement : $subject;
	}
	
	/**
	 * Overrides the current subject with the given value, if the subject (not trimmed) is empty.
	 * 
	 * @param  string				$subject: The current subject in the processor chain
	 * @param  string				$replacement: The value that overrides the subject
	 * @return string				The new subject
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function processor_ifBlank($subject, $replacement) {
		return (!F3_PHP6_Functions::strlen((string)$subject)) ? $replacement : $subject;
	}
	
	/**
	 * Trims the current subject (Removes whitespaces arround the value).
	 * 
	 * @param  string				$subject: The current subject in the processor chain
	 * @return string				The new trimmed subject
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function processor_trim($subject) {
		return trim((string)$subject);
	}
	
	/**
	 * Returns the trueValue when the condition evaluates to TRUE, otherwise 
	 * the falseValue is returned.
	 * 
	 * If the condition is a TypoScript object, it is handled as a string.
	 * 
	 * The following conditions are considered TRUE:
	 * 
	 *   - boolean TRUE
	 *   - number > 0
	 *   - non-empty string
	 * 
	 * While these conditions evaluate to FALSE:
	 * 
	 *   - boolean FALSE
	 *   - number <= 0
	 *   - empty string
	 * 
	 * @param  string				$subject: The current subject in the processor chain
	 * @param  boolean				$condition: The condition for the if clause, or simply TRUE/FALSE
	 * @param  string				$trueValue: This is returned if $condition is TRUE
	 * @param  string				$falseValue: This is returned if $condition is FALSE
	 * @return mixed				The calculated return value. Either $trueValue or $falseValue
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function processor_if($subject, $condition, $trueValue, $falseValue) {
		if (!is_bool($condition)) {
			if (is_object($condition)) $condition = (string)$condition;
			if ((is_numeric($condition) && $condition <= 0) || $condition === '') $condition = FALSE;
			if ($condition === 1 || (is_string($condition) && F3_PHP6_Functions::strlen($condition) > 0)) $condition = TRUE;
		}
		if (!is_bool($condition)) throw new F3_TypoScript_Exception('The condition in the if processor could not be converted to boolean. Got: ('.gettype($condition).')' . (string)$condition, 1185355020);
		
		return ($condition ? $trueValue : $falseValue);
	}
	
	/**
	 * Returns TRUE, if the subject (trimmed) is empty.
	 * 
	 * @param  string				$subject: The current subject in the processor chain
	 * @return boolean				The calculated return value. Either TRUE or FALSE
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function processor_isEmpty($subject) {
		return (trim((string)$subject) === '' || trim((string)$subject) === '0');
	}
	
	/**
	 * Returns TRUE, if the subject (not trimmed) is blank.
	 * 
	 * @param  string				$subject: The current subject in the processor chain
	 * @return boolean				TRUE if the subject is blank, otherwise FALSE
	 * @author Andreas Förthner <andreas.foerthner@netlogix.de>
	 */
	public function processor_isBlank($subject) {
		return (!F3_PHP6_Functions::strlen((string)$subject));
	}
}
?>