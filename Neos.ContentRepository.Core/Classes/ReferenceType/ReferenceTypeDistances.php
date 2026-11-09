<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType;

/**
 * A dictionary of reference type distances relative to another reference type; indexed by name, value is the distance
 *
 * @api
 */
final class ReferenceTypeDistances
{
    /**
     * @param array<string,int> $items
     */
    private function __construct(
        public array $items,
    ) {
    }

    /**
     * @param array<string,int> $items
     */
    public static function create(array $items): self
    {
        return new self($items);
    }

    public function getDistance(ReferenceTypeName $referenceTypeName): ?int
    {
        return $this->items[$referenceTypeName->value] ?? null;
    }

    public function toList(): ReferenceTypeNameList
    {
        return ReferenceTypeNameList::fromStringArray(array_keys($this->items));
    }
}
