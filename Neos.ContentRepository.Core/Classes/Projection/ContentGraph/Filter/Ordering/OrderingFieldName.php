<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering;

use JsonSerializable;

/**
 * The name of an {@see OrderingField} this is usually either a node property name or one of the timestamp fields
 *
 * @api This class is used for the {@see ContentSubgraphInterface} ordering
 */
final class OrderingFieldName implements JsonSerializable
{
    public readonly string $value;

    private function __construct(string $value)
    {
        $this->value = trim($value);
        if ($this->value === '') {
            throw new \InvalidArgumentException('Ordering field value must not be empty', 1680269479);
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
