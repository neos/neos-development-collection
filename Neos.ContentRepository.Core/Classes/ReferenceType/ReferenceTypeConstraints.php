<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType;

/**
 * Constraints to reduce the amount of available reference types.
 *
 * @api
 */
final readonly class ReferenceTypeConstraints
{
    private function __construct(
        public bool $isWildcardAllowed,
        public bool $includeAbstract,
        public ReferenceTypeNameSet $explicitlyAllowedReferenceTypeNames,
        public ReferenceTypeNameSet $explicitlyDisallowedReferenceTypeNames,
    ) {
    }

    public static function create(
        bool $isWildcardAllowed,
        bool $includeAbstract,
        ReferenceTypeNameSet $explicitlyAllowedNodeTypes,
        ReferenceTypeNameSet $explicitlyDisallowedNodeTypes,
    ): self {
        return new self(
            isWildcardAllowed: $isWildcardAllowed,
            includeAbstract: $includeAbstract,
            explicitlyAllowedReferenceTypeNames: $explicitlyAllowedNodeTypes,
            explicitlyDisallowedReferenceTypeNames: $explicitlyDisallowedNodeTypes,
        );
    }
}
