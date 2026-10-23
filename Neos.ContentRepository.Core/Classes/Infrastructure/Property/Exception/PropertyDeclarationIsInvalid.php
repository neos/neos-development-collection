<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes\Exception;

use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;

/**
 * The exception to be thrown if an invalid reference type declaration was attempted to be initialized
 * @internal
 */
final class PropertyDeclarationIsInvalid extends \Exception
{
    public static function becauseItsDefaultValueDoesNotMatchItsType(
        PropertyName $propertyName,
        PropertyType $propertyType,
        mixed $defaultValue,
        NodeTypeName|ReferenceTypeName|null $declaringType,
    ): self {
        if ($declaringType !== null) {
            $declaratorText = ' on '. ($declaringType instanceof NodeTypeName ? 'node type' : 'reference type')
                . ' "' . $declaringType->value . '"';
        } else {
            $declaratorText = '';
        }

        return new self(
            'Declaration of property "' . $propertyName->value . '"' . $declaratorText
                . ' is invalid; its default value is of type "'
                . get_debug_type($defaultValue) . '", but expected was "' . $propertyType->value . '".',
            1792416377,
        );
    }
}
