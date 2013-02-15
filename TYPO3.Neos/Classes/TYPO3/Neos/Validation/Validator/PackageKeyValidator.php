<?php
namespace TYPO3\Neos\Validation\Validator;

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

/**
 * Validator for package keys
 */
class PackageKeyValidator extends \TYPO3\Flow\Validation\Validator\RegularExpressionValidator {

	/**
	 * @var array
	 */
	protected $supportedOptions = array(
		'regularExpression' => array(\TYPO3\Flow\Package\PackageInterface::PATTERN_MATCH_PACKAGEKEY, 'The regular expression to use for validation, used as given', 'string')
	);

}
?>