<?php
namespace TYPO3\Neos\Validation\Validator;

/*
 * This file is part of the TYPO3.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use TYPO3\Flow\Annotations as Flow;
use TYPO3\Flow\Package\PackageInterface;
use TYPO3\Flow\Validation\Validator\RegularExpressionValidator;

/**
 * Validator for package keys
 */
class PackageKeyValidator extends RegularExpressionValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = array(
        'regularExpression' => array(PackageInterface::PATTERN_MATCH_PACKAGEKEY, 'The regular expression to use for validation, used as given', 'string')
    );
}
