<?php
namespace Neos\Neos\Validation\Validator;

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Package\PackageInterface;
use Neos\Flow\Validation\Validator\RegularExpressionValidator;

/**
 * Validator for package keys
 */
class PackageKeyValidator extends RegularExpressionValidator
{
    /**
     * @var array
     */
    protected $supportedOptions = [
        'regularExpression' => [
            PackageInterface::PATTERN_MATCH_PACKAGEKEY,
            'The regular expression to use for validation, used as given',
            'string'
        ]
    ];
}
