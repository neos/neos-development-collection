<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\Node;

/**
 * @implements \IteratorAggregate<int, PropertyName>
 * @api
 */
final readonly class PropertyNames implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * @var array<int, PropertyName>
     */
    private array $values;

    /**
     * @no-named-arguments
     */
    private function __construct(
        PropertyName ...$propertyNames
    ) {
        $this->values = $propertyNames;
    }

    /**
     * @param array<string|PropertyName> $propertyNames
     */
    public static function fromArray(array $propertyNames): self
    {
        $values = [];
        foreach ($propertyNames as $propertyName) {
            $values[] = is_string($propertyName) ? PropertyName::fromString($propertyName) : $propertyName;
        }
        return new self(...$values);
    }

    public static function createEmpty(): self
    {
        return new self();
    }

    public function merge(self $other): self
    {
        return new self(...$this->values, ...$other->values);
    }

    public function getDifference(self $other): self
    {
        $otherPropertyNames = [];
        foreach ($other->values as $propertyName) {
            $otherPropertyNames[$propertyName->value] = true;
        }
        $difference = [];
        foreach ($this->values as $propertyName) {
            if (!isset($otherPropertyNames[$propertyName->value])) {
                $difference[] = $propertyName;
            }
        }
        return new self(...$difference);
    }

    public function jsonSerialize(): mixed
    {
        return $this->values;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->values;
    }

    public function isEmpty(): bool
    {
        return $this->values === [];
    }

    public function count(): int
    {
        return count($this->values);
    }
}
