<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType;

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

    public function getReferenceTypeDeclaration(ReferenceTypeName $name): ?ReferenceTypeDeclaration
    {
        return $this->items[$name->value] ?? null;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }
}
