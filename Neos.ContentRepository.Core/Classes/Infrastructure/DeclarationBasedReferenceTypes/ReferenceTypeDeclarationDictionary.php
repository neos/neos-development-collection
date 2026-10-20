<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes;

use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeGraph;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;
use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeNameList;

/**
 * @internal
 * @implements \IteratorAggregate<string,ReferenceTypeDeclaration>
 */
final readonly class ReferenceTypeDeclarationDictionary implements \IteratorAggregate
{
    /**
     * @param array<string,ReferenceTypeDeclaration> $items indexed by name
     */
    private function __construct(
        private array $items,
    ) {
    }

    public static function create(ReferenceTypeDeclaration ...$list): self
    {
        $items = [];
        foreach ($list as $item) {
            $items[$item->name->value] = $item;
        }

        return new self($items);
    }

    /**
     * @param array<string,array<string,mixed>> $values
     */
    public static function fromArray(array $values): self
    {
        $items = [];
        foreach ($values as $name => $item) {
            $items[$name] = ReferenceTypeDeclaration::fromArray($item, $name);
        }

        return new self($items);
    }

    public function getReferenceTypeDeclaration(ReferenceTypeName $name): ?ReferenceTypeDeclaration
    {
        return $this->items[$name->value] ?? null;
    }

    public function expandToGraph(): ReferenceTypeGraph
    {
        $referenceTypes = [];
        $superTypeNames = [];
        $declaredSubTypeNames = [];
        $subTypeNames = [];

        foreach ($this->items as $declaration) {
            if (!array_key_exists($declaration->name->value, $referenceTypes)) {
                $declaration->resolveReferenceTypeHierarchy(
                    dictionary: $this,
                    hierarchySoFar: ReferenceTypeNameList::list(),
                    referenceTypes: $referenceTypes,
                    superTypeNames: $superTypeNames,
                    declaredSubTypeNames: $declaredSubTypeNames,
                    subTypeNames: $subTypeNames,
                );
            }
        }

        return ReferenceTypeGraph::create(
            declaredSuperTypeNames: array_map(
                fn (ReferenceTypeDeclaration $declaration): ReferenceTypeNameList => $declaration->superTypeNames,
                $this->items,
            ),
            superTypeNames: array_map(
                fn (array $superTypeNamesForSubType): ReferenceTypeNameList
                => ReferenceTypeNameList::list(...$superTypeNamesForSubType),
                $superTypeNames,
            ),
            declaredSubTypesNames: array_map(
                fn (array $declaredSubTypeNamesForSuperType): ReferenceTypeNameList
                => ReferenceTypeNameList::list(...$declaredSubTypeNamesForSuperType),
                $declaredSubTypeNames,
            ),
            subTypeNames: array_map(
                fn (array $subTypeNamesForSuperType): ReferenceTypeNameList
                => ReferenceTypeNameList::list(...$subTypeNamesForSuperType),
                $subTypeNames,
            ),
            referenceTypes: $referenceTypes,
        );
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }
}
