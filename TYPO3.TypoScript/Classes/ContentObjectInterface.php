<?php
namespace TYPO3\TypoScript;

/*                                                                        *
 * This script belongs to the FLOW3 package "TypoScript".                 *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Contract for TypoScript Content Objects
 *
 */
interface ContentObjectInterface extends \TYPO3\TypoScript\ObjectInterface, \TYPO3\Fluid\Core\Parser\SyntaxTree\RenderingContextAwareInterface {

	/**
	 * Returns the rendered content of this content object
	 *
	 * @return string The rendered content as a string - usually (X)HTML, XML or just plaing text
	 */
	public function render();
}
?>