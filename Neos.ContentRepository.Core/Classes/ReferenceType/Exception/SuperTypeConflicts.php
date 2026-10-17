<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\ReferenceType\Exception;

/**
 * @implements \IteratorAggregate<string,list<SuperTypeConflict>>
 * @internal
 */
final readonly class SuperTypeConflicts implements \IteratorAggregate
{
    /**
     * @param array<string,list<SuperTypeConflict>> $items
     */
    private function __construct(
        public array $items,
    ) {
    }

    /**
     * @param array<string,list<SuperTypeConflict>> $items
     */
    public static function create(
        array $items,
    ): self {
        return new self($items);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    public function toDebugString(): string
    {
        return implode(', ', array_map(
            fn (array $conflictsByPropertyName, string $propertyName): string => 'property "' . $propertyName . '": '
                . implode(
                    ', ',
                    array_map(
                        fn (SuperTypeConflict $conflict): string => $conflict->toDebugString(),
                        $conflictsByPropertyName,
                    )
                ),
            $this->items,
            array_keys($this->items),
        ));
    }
}
