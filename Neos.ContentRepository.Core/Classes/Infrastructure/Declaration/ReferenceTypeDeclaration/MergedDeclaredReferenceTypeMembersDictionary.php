<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\Declaration\ReferenceTypeDeclaration;

use Neos\ContentRepository\Core\ReferenceType\ReferenceTypeName;

/**
 * @internal
 * @implements \IteratorAggregate<string,MergedDeclaredReferenceTypeMembers>
 */
final class MergedDeclaredReferenceTypeMembersDictionary implements \IteratorAggregate
{
    /**
     * @param array<string,MergedDeclaredReferenceTypeMembers> $items indexed by name
     */
    private function __construct(
        private array $items,
    ) {
    }

    public static function create(): self
    {
        return new self([]);
    }

    public function add(MergedDeclaredReferenceTypeMembers $item): void
    {
        $this->items[$item->name->value] = $item;
    }

    public function getMergedDeclaredReferenceTypeMembers(ReferenceTypeName $name): ?MergedDeclaredReferenceTypeMembers
    {
        return $this->items[$name->value] ?? null;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }
}
