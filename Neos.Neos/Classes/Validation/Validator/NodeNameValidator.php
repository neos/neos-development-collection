<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Validation\Validator;

use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\Flow\Validation\Validator\RegularExpressionValidator;

/**
 * Validator for node names
 */
class NodeNameValidator extends RegularExpressionValidator
{
    /**
     * @var array<string,mixed>
     */
    protected $supportedOptions = [
        'regularExpression' => [
            NodeName::PATTERN,
            'The regular expression to use for validation, used as given',
            'string'
        ]
    ];
}
