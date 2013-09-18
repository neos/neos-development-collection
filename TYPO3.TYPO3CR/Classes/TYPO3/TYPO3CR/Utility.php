<?php
namespace TYPO3\TYPO3CR;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3CR".               *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * A class holding utility methods
 *
 * @api
 */
class Utility {

	/**
	 * Transforms a text (for example a node title) into a valid node name  by removing invalid characters and
	 * transliterating special characters if possible.
	 *
	 * @param string $name The possibly invalid node name
	 * @return string A valid node name
	 */
	static public function renderValidNodeName($name) {
		if (class_exists('Transliterator', FALSE)) {
			$transliterator = \Transliterator::create('Any-Latin; NFD; [:Nonspacing Mark:] Remove; NFC; [:Punctuation:] Remove; Latin-ASCII; Lower();');
			$name = $transliterator->transliterate($name);
			return preg_replace('/[-\s]+/', '-', $name);
		} else {
			// "transliterate" some special characters
			$transliteration = array(
				'ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss', 'Ä' => 'ae', 'Ö' => 'oe', 'Ü' => 'ue',
				'å' => 'aa', 'æ' => 'ae', 'ø' => 'oe', 'œ' => 'oe', 'Å' => 'aa', 'Æ' => 'ae', 'Ø' => 'oe', 'Œ' => 'oe',
				'#' => 'no',
				'&' => 'and'
			);
			$name = strtr($name, $transliteration);

			// "transliterate" known HTML entities
			$name = htmlentities($name, ENT_QUOTES, 'UTF-8');
			$name = preg_replace(
				'/&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);/i',
				'$1',
				$name
			);
			$name = html_entity_decode($name, ENT_QUOTES, 'UTF-8');

			// replace any leftovers with a dash
			$name = preg_replace('/[^0-9a-z]+/i', '-', $name);

			// trim leftover dashes, lowercase and return
			return strtolower(trim($name, '-'));
		}
	}
}

?>