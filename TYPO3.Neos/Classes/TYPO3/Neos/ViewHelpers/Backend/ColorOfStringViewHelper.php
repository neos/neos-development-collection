<?php
namespace TYPO3\Neos\ViewHelpers\Backend;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TYPO3.Neos".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * Generates a color code for a given string
 */
class ColorOfStringViewHelper extends AbstractViewHelper {

	/**
	 * Outputs a hex color code (#000000) based on $text
	 *
	 * @param string $string
	 * @param integer $minimalBrightness
	 * @return string
	 * @throws \Exception
	 */
	public function render($string = NULL, $minimalBrightness = 50) {
		if ($minimalBrightness < 0 or $minimalBrightness > 255) {
			throw new \Exception('Minimal brightness should be between 0 and 255', 1417553921);
		}

		if ($string === NULL) {
			$string = $this->renderChildren();
		}

		$hash = md5($string);

		$rgbValues = array();
		for ($i = 0; $i < 3; $i++) {
			$rgbValues[$i] = max(array(
				round(hexdec(substr($hash, 10 * $i, 10)) / hexdec('FFFFFFFFFF') * 255),
				$minimalBrightness
			));
		}

		$output = '#';
		for ($i = 0; $i < 3; $i++) {
			$output .= str_pad(dechex($rgbValues[$i]), 2, 0, STR_PAD_LEFT);
		}

		return $output;
	}
}