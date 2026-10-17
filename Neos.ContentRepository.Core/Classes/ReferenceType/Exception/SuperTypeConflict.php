<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType\Exception;

use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;

/**
 * @internal
 */
final readonly class SuperTypeConflict
{
    private function __construct(
        public ReferenceTypeName $referenceTypeName,
        public PropertyType $propertyType,
    ) {
    }

    public static function create(
        ReferenceTypeName $referenceTypeName,
        PropertyType $propertyType,
    ): self {
        return new self(
            referenceTypeName: $referenceTypeName,
            propertyType: $propertyType,
        );
    }

    public function toDebugString(): string
    {
        return 'reference type "' . $this->referenceTypeName->value
            . '" declares it as "' . $this->propertyType->value . '"';
    }
}
