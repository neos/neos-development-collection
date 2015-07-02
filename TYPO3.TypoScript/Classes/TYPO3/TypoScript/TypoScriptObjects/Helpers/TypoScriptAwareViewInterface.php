<?php
namespace TYPO3\TypoScript\TypoScriptObjects\Helpers;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\TypoScript\TypoScriptObjects\AbstractTypoScriptObject;

/**
 * You should implement this interface with a View that should allow access
 * to the TypoScript object it is rendered from (and so the TypoScript runtime).
 *
 * The TypoScript FluidView is the reference implementation for this.
 * @see \TYPO3\TypoScript\TypoScriptObjects\Helpers\FluidView
 *
 * @api
 */
interface TypoScriptAwareViewInterface {

	/**
	 * @return AbstractTypoScriptObject
	 */
	public function getTypoScriptObject();

}
