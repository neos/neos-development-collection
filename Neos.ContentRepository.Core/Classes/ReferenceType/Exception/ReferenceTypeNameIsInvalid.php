<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType\Exception;

/**
 * The exception to be thrown if an invalid reference type name was attempted to be initialized
 * @api
 */
final class ReferenceTypeNameIsInvalid extends \InvalidArgumentException
{
    public static function becauseItDoesNotMatchTheRequiredPattern(string $attemptedValue): self
    {
        return new self(
            'Invalid reference type name "' . $attemptedValue
                . '" (a reference type name must be of length 1-255, start with a character '
                . ' and only contain characters, numbers, "." and ":").',
            1792133185
        );
    }
}
