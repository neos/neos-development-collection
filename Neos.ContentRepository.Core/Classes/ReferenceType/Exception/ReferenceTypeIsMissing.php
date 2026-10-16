<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType\Exception;

use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;

/**
 * The exception to be thrown if given reference type name cannot be resolved to a reference type
 * @api
 */
final class ReferenceTypeIsMissing extends \Exception
{
    public static function butWasRequired(ReferenceTypeName $requiredName): self
    {
        return new self(
            'Reference type "' . $requiredName->value . '" is unknown to the reference type graph.',
            1792139965,
        );
    }
}
