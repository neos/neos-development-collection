<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Node;

use Neos\ContentRepository\Core\Infrastructure\Property\PropertyType;
use Neos\ContentRepository\Core\ReferenceType\Exception\SuperTypeConflict;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;

/**
 * @api
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
        self $subTypePropertyMetadata,
        array &$superTypeConflicts,
        ReferenceTypeName $referenceTypeName,
        ?ReferenceTypeName $previousReferenceTypeName = null,
    ): self {
        $items = $this->items;
        foreach ($subTypePropertyMetadata->items as $key => $item) {
            if (array_key_exists($key, $items)) {
                $items[$key] = $items[$key]->mergeToSubType($item, $superTypeConflicts, $referenceTypeName, $previousReferenceTypeName);
            } else {
                $items[$key] = $item;
            }
        }

        return new self($items);
    }

    public function hasProperty(PropertyName $propertyName): bool
    {
        return array_key_exists($propertyName->value, $this->items);
    }

    public function getPropertyType(PropertyName $propertyName): ?PropertyType
    {
        return ($this->items[$propertyName->value] ?? null)?->type;
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
