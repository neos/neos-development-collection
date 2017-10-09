<?php
namespace Neos\ContentRepository\Validation\Validator;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Flow\Validation\Validator\AbstractValidator;

/**
 * Validator for node identifiers
 *
 * @api
 * @todo: Replace pattern by value object
 * @Flow\Scope("singleton")
 */
class NodeIdentifierValidator extends AbstractValidator
{
    /**
     * A preg pattern to match against node identifiers
     * @var string
     */
    const PATTERN_MATCH_NODE_IDENTIFIER = '/^([a-z0-9\-]{1,255})$/';

    /**
     * Checks if the given value is a syntactically valid node identifier.
     *
     * @param mixed $value The value that should be validated
     * @return void
     * @api
     */
    protected function isValid($value)
    {
        if (!is_string($value) || !preg_match(self::PATTERN_MATCH_NODE_IDENTIFIER, $value)) {
            $this->addError('The given subject was not a valid node identifier.', 1489921024);
        }
    }
}
