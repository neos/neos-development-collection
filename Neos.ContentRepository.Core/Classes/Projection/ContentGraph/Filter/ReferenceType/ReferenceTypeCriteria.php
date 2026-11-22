<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\ReferenceType;

use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeNameSet;
use Neos\Utility\Arrays;

/**
 * The list of reference type criteria needed for various find() operations on the {@see ContentSubgraphInterface}.
 *
 * This DTO is a direct representation of the user's intent, so the filter strings are translated
 * as follows:
 * - Foo.Bar: `allowed: [Foo.Bar]; disallowed: []`
 * - Foo.Bar,Baz,!Other: `allowed: [Foo.Bar, Baz]; disallowed: [Other]`
 * - (empty): `allowed: []; disallowed: []`.
 *
 * This is usually created by the factory method {@see self::fromFilterString}.
 *
 *
 * ## Semantics
 *
 * 1) By specifying a reference type, you allow or deny it *and all of its sub reference types*
 *    according to the reference type hierarchy.
 *
 * 2) Deny rules win over allow rules.
 *
 * 3) If no constraint is specified, everything is allowed.
 *
 * 4) If only deny rules are specified, everything is allowed **except** the specified reference types
 *    and their sub reference types.
 *
 * NOTE: Implementers of ContentSubgraphInterface need to take of implementing the above
 *       semantics. You can use the internal method {@see ExpandedReferenceTypeCriteria::create}
 *       to take sub reference types into account.
 *
 * @api
 */
final readonly class ReferenceTypeCriteria
{
    private function __construct(
        public ReferenceTypeNameSet $explicitlyAllowedReferenceTypeNames,
        public ReferenceTypeNameSet $explicitlyDisallowedReferenceTypeNames,
    ) {
    }

    /**
     * We recommended to call this method with named arguments to better
     * understand the distinction between allowed and disallowed ReferenceTypeNames
     */
    public static function create(
        ReferenceTypeNameSet $allowed,
        ReferenceTypeNameSet $disallowed
    ): self {
        return new self($allowed, $disallowed);
    }

    public static function createWithAllowedReferenceTypeNames(ReferenceTypeNameSet $referenceTypeNames): self
    {
        return new self($referenceTypeNames, ReferenceTypeNameSet::create());
    }

    public static function createWithDisallowedReferenceTypeNames(ReferenceTypeNameSet $referenceTypeNames): self
    {
        return new self($referenceTypeNames, ReferenceTypeNameSet::create());
    }

    public static function fromFilterString(string $serializedFilters): self
    {
        $explicitlyAllowedReferenceTypeNames = [];
        $explicitlyDisallowedReferenceTypeNames = [];

        $referenceTypeFilterParts = Arrays::trimExplode(',', $serializedFilters);
        foreach ($referenceTypeFilterParts as $referenceTypeFilterPart) {
            if (\mb_strpos($referenceTypeFilterPart, '!') === 0) {
                $negate = true;
                $referenceTypeFilterPart = \mb_substr($referenceTypeFilterPart, 1);
            } else {
                $negate = false;
            }

            if ($negate) {
                $explicitlyDisallowedReferenceTypeNames[] = $referenceTypeFilterPart;
            } else {
                $explicitlyAllowedReferenceTypeNames[] = $referenceTypeFilterPart;
            }
        }

        return new self(
            ReferenceTypeNameSet::fromStringArray($explicitlyAllowedReferenceTypeNames),
            ReferenceTypeNameSet::fromStringArray($explicitlyDisallowedReferenceTypeNames),
        );
    }

    /**
     * IMMUTABLE, returns a new instance
     */
    public function withAdditionalDisallowedReferenceType(ReferenceTypeName $referenceTypeName): self
    {
        return new self(
            $this->explicitlyAllowedReferenceTypeNames,
            $this->explicitlyDisallowedReferenceTypeNames->withAdditionalReferenceTypeName($referenceTypeName)
        );
    }

    /**
     * IMMUTABLE, returns a new instance
     */
    public function withAdditionalAllowedReferenceType(ReferenceTypeName $referenceTypeName): self
    {
        return new self(
            $this->explicitlyAllowedReferenceTypeNames->withAdditionalReferenceTypeName($referenceTypeName),
            $this->explicitlyDisallowedReferenceTypeNames
        );
    }

    public function toFilterString(): string
    {
        $parts = [];
        foreach ($this->explicitlyDisallowedReferenceTypeNames as $referenceTypeName) {
            $parts[] = '!' . $referenceTypeName->value;
        }

        foreach ($this->explicitlyAllowedReferenceTypeNames as $referenceTypeName) {
            $parts[] = $referenceTypeName->value;
        }

        return implode(',', $parts);
    }
}
