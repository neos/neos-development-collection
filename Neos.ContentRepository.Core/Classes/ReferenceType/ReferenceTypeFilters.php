<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType;

/**
 * Filters to reduce the amount of available reference types.
 *
 * @api
 */
final readonly class ReferenceTypeFilters
{
    private function __construct(
        public bool $includeWildcard,
        public bool $includeAbstract,
        public ReferenceTypeNameSet $explicitlyIncludedReferenceTypeNames,
        public ReferenceTypeNameSet $explicitlyExcludedReferenceTypeNames,
    ) {
    }

    public static function create(
        bool $includeWildcard,
        bool $includeAbstract,
        ReferenceTypeNameSet $explicitlyIncludedReferenceTypeNames,
        ReferenceTypeNameSet $explicitlyExcludedReferenceTypeNames,
    ): self {
        return new self(
            includeWildcard: $includeWildcard,
            includeAbstract: $includeAbstract,
            explicitlyIncludedReferenceTypeNames: $explicitlyIncludedReferenceTypeNames,
            explicitlyExcludedReferenceTypeNames: $explicitlyExcludedReferenceTypeNames,
        );
    }
}
