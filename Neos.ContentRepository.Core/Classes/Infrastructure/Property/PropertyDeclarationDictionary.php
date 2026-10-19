<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\Property;

use Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes\Exception\SuperTypeConflict;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\SharedModel\Property\Property;
use Neos\ContentRepository\Core\SharedModel\Property\PropertyDictionary;

/**
 * @internal
 * @implements \IteratorAggregate<string,PropertyDeclaration>
 */
final readonly class PropertyDeclarationDictionary implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @param array<string,PropertyDeclaration> $items
     */
    private function __construct(
        public array $items,
    ) {
    }

    public static function create(PropertyDeclaration ...$declarations): self
    {
        $items = [];
        foreach ($declarations as $declaration) {
            $items[$declaration->name->value] = $declaration;
        }
        return new self($items);
    }

    /**
     * @param array<string,list<SuperTypeConflict>> $superTypeConflicts
     * @param-out array<string,list<SuperTypeConflict>> $superTypeConflicts
     */
    public function mergeToSubType(
        self|PropertyDictionary $subTypeProperties,
        array &$superTypeConflicts,
        ReferenceTypeName $referenceTypeName,
        ?ReferenceTypeName $previousReferenceTypeName = null,
    ): self {
        if ($subTypeProperties instanceof PropertyDictionary) {
            $subTypeProperties = self::fromPropertyDictionary($subTypeProperties);
        }
        $items = $this->items;
        foreach ($subTypeProperties->items as $key => $item) {
            if (array_key_exists($key, $items)) {
                $items[$key] = $items[$key]->mergeToSubType($item, $superTypeConflicts, $referenceTypeName, $previousReferenceTypeName);
            } else {
                $items[$key] = $item;
            }
        }

        return new self($items);
    }

    public static function fromPropertyDictionary(PropertyDictionary $dictionary): self
    {
        return new self(array_map(
            fn (Property $item): PropertyDeclaration => PropertyDeclaration::fromProperty($item),
            $dictionary->items,
        ));
    }

    public function toPropertyDictionary(): PropertyDictionary
    {
        return PropertyDictionary::create(array_map(
            fn (PropertyDeclaration $item): Property => $item->toProperty(),
            $this->items,
        ));
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
