<?php
namespace TYPO3\Neos\ViewHelpers;

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
 * View helper to check if a given value is an array.
 *
 * = Examples =
 *
 * <code title="Basic usage">
 * {neos:getType(value: 'foo')}
 * </code>
 * <output>
 * string
 * </output>
 *
 * <code title="Use with shorthand syntax">
 * {myValue -> neos:getType()}
 * </code>
 * <output>
 * string
 * (if myValue is a string)
 * </output>
 */
class GetTypeViewHelper extends AbstractViewHelper {

	/**
	 * @param mixed $value The value to determine the type of
	 * @return string
	 */
	public function render($value = NULL) {
		return gettype($value ?: $this->renderChildren());
	}

}
