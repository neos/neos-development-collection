<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\DeclarationBasedReferenceTypes;

use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeDistances;
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
        $superTypeDistances = [];
        $declaredSubTypeNames = [];
        $subTypeDistances = [];
        $currentHierarchyLevel = 0;

        foreach ($this->items as $declaration) {
            if (!array_key_exists($declaration->name->value, $referenceTypes)) {
                $declaration->resolveReferenceTypeHierarchy(
                    dictionary: $this,
                    hierarchySoFar: ReferenceTypeNameList::list(),
                    referenceTypes: $referenceTypes,
                    superTypeDistances: $superTypeDistances,
                    declaredSubTypeNames: $declaredSubTypeNames,
                    subTypeDistances: $subTypeDistances,
                    currentHierarchyLevel: $currentHierarchyLevel,
                );
            }
        }

        return ReferenceTypeGraph::create(
            declaredSuperTypeNames: array_map(
                fn (ReferenceTypeDeclaration $declaration): ReferenceTypeNameList => $declaration->superTypeNames,
                $this->items,
            ),
            superTypeDistances: array_map(
                fn (array $superTypeDistancesForSubType): ReferenceTypeDistances
                    => ReferenceTypeDistances::create($superTypeDistancesForSubType),
                $superTypeDistances,
            ),
            declaredSubTypesNames: array_map(
                fn (array $declaredSubTypeNamesForSuperType): ReferenceTypeNameList
                    => ReferenceTypeNameList::list(...$declaredSubTypeNamesForSuperType),
                $declaredSubTypeNames,
            ),
            subTypeDistances: array_map(
                fn (array $subTypeNamesForSuperType): ReferenceTypeDistances
                    => ReferenceTypeDistances::create($subTypeNamesForSuperType),
                $subTypeDistances,
            ),
            referenceTypes: $referenceTypes,
        );
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }
}
