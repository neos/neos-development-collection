<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Infrastructure\Declaration\ReferenceTypeDeclaration;

/**
 * @template T of ReferenceMetadataDeclarationInterface
 * @implements \IteratorAggregate<class-string<T>,class-string<T>>
 * @internal
 */
final readonly class ReferenceMetadataDeclarationImplementationRegistry implements \IteratorAggregate
{
    /**
     * @param array<class-string<T>,class-string<T>> $items
     */
    private function __construct(
        public array $items,
    ) {
    }

    /**
     * @template I of ReferenceMetadataDeclarationInterface
     * @param class-string<I> ...$declarations
     * @return self<I>
     */
    public static function create(string ...$declarations): self
    {
        $items = [];
        foreach ($declarations as $declaration) {
            $items[$declaration] = $declaration;
        }

        return new self($items);
    }

    /**
     * @param array<string,mixed> $values
     * @return self<ReferenceMetadataDeclarationInterface>
     */
    public static function fromArray(array $values): self
    {
        $declarations = [];
        foreach ($values as $value => $isActive) {
            if (
                $isActive === true
                && class_exists($value)
                && in_array(ReferenceMetadataDeclarationInterface::class, class_implements($value))
            ) {
                /** @var class-string<ReferenceMetadataDeclarationInterface> $value */
                $declarations[] = $value;
            }
        }

        return self::create(...$declarations);
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    /**
     * @return array<class-string<T>,class-string<T>>
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}
