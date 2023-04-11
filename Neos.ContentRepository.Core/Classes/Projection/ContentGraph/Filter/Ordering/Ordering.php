<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering;

use ArrayIterator;
use IteratorAggregate;
use JsonSerializable;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use Traversable;

/**
 * @api This class is used for the {@see ContentSubgraphInterface} ordering
 * @implements IteratorAggregate<OrderingField>
 */
final class Ordering implements IteratorAggregate, JsonSerializable
{
    /**
     * @var OrderingField[]
     */
    private array $fields;

    private function __construct(OrderingField ...$fields)
    {
        if ($fields === []) {
            throw new \InvalidArgumentException('Ordering must contain at least one ordering field', 1680263229);
        }
        $this->fields = $fields;
    }

    /**
     * @param array<string, mixed> $array
     */
    public static function fromArray(array $array): self
    {
        $fields = [];
        foreach ($array as $field) {
            $fields[] = $field instanceof OrderingField ? $field : OrderingField::fromArray($field);
        }
        return new self(...$fields);
    }

    public static function byProperty(PropertyName $propertyName, OrderingDirection $direction): self
    {
        return new self(OrderingField::byProperty($propertyName, $direction));
    }

    public static function byTimestampField(TimestampField $timestampField, OrderingDirection $direction): self
    {
        return new self(OrderingField::byTimestampField($timestampField, $direction));
    }

    public function andByProperty(PropertyName $propertyName, OrderingDirection $direction): self
    {
        return new self(...[...$this->fields, OrderingField::byProperty($propertyName, $direction)]);
    }

    public function andByTimestampField(TimestampField $timestampField, OrderingDirection $direction): self
    {
        return new self(...[...$this->fields, OrderingField::byTimestampField($timestampField, $direction)]);
    }

    public function getIterator(): Traversable
    {
        return new ArrayIterator($this->fields);
    }

    /**
     * @return OrderingField[]
     */
    public function jsonSerialize(): array
    {
        return $this->fields;
    }
}
