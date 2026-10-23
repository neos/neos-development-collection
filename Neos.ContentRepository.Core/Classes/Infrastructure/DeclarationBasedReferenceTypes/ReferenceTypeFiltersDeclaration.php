<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes;

use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeFilters;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeNameSet;
use Neos\Utility\Arrays;

/**
 * @api
 */
final readonly class ReferenceTypeFiltersDeclaration implements \JsonSerializable
{
    /**
     * @param array<string,bool> $filteredReferenceTypes indexed by reference type name, value whether it is to be included or not
     */
    private function __construct(
        public bool $includeWildcard,
        public array $filteredReferenceTypes,
    ) {
    }

    /**
     * @param array<string,bool> $filteredReferenceTypes indexed by reference type name, value whether it is to be included or not
     */
    public static function create(
        bool $includeWildcard,
        array $filteredReferenceTypes,
    ): self {
        return new self(
            includeWildcard: $includeWildcard,
            filteredReferenceTypes: $filteredReferenceTypes,
        );
    }

    /**
     * @param array<string,bool> $values
     */
    public static function fromArray(array $values): self
    {
        if (array_key_exists('*', $values)) {
            $includeWildcard = $values['*'];
            unset($values['*']);
        } else {
            $includeWildcard = false;
        }

        return new self(
            includeWildcard: $includeWildcard,
            filteredReferenceTypes: $values,
        );
    }

    public static function fromReferenceTypeFilters(ReferenceTypeFilters $referenceTypeFilters): self
    {
        $filteredReferenceTypes = [];
        foreach ($referenceTypeFilters->explicitlyIncludedReferenceTypeNames as $explicitlyIncludedReferenceTypeName) {
            $filteredReferenceTypes[$explicitlyIncludedReferenceTypeName->value] = true;
        }
        foreach ($referenceTypeFilters->explicitlyExcludedReferenceTypeNames as $explicitlyExcludedReferenceTypeName) {
            $filteredReferenceTypes[$explicitlyExcludedReferenceTypeName->value] = false;
        }

        return new self(
            includeWildcard: $referenceTypeFilters->includeWildcard,
            filteredReferenceTypes: $filteredReferenceTypes,
        );
    }

    public function mergeToSubType(self|ReferenceTypeFilters $subTypeReferenceTypeConstraints): self
    {
        if ($subTypeReferenceTypeConstraints instanceof ReferenceTypeFilters) {
            $subTypeReferenceTypeConstraints = self::fromReferenceTypeFilters($subTypeReferenceTypeConstraints);
        }

        return new self(
            includeWildcard: $subTypeReferenceTypeConstraints->includeWildcard,
            filteredReferenceTypes: Arrays::arrayMergeRecursiveOverrule(
                $this->filteredReferenceTypes,
                $subTypeReferenceTypeConstraints->filteredReferenceTypes,
            ),
        );
    }

    public function toReferenceTypeFilters(): ReferenceTypeFilters
    {
        $explicitlyIncludedReferenceTypeNames = [];
        $explicitlyExcludedReferenceTypeNames = [];
        foreach ($this->filteredReferenceTypes as $referenceTypeName => $isToBeIncluded) {
            if ($isToBeIncluded) {
                $explicitlyIncludedReferenceTypeNames[] = $referenceTypeName;
            } else {
                $explicitlyExcludedReferenceTypeNames[] = $referenceTypeName;
            }
        }
        return ReferenceTypeFilters::create(
            includeWildcard: $this->includeWildcard,
            includeAbstract: true,
            explicitlyIncludedReferenceTypeNames: ReferenceTypeNameSet::fromStringArray($explicitlyIncludedReferenceTypeNames),
            explicitlyExcludedReferenceTypeNames: ReferenceTypeNameSet::fromStringArray($explicitlyExcludedReferenceTypeNames),
        );
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return get_object_vars($this);
    }
}
