<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Ordering;

use InvalidArgumentException;
use JsonSerializable;
use Neos\ContentRepository\Core\SharedModel\Node\PropertyName;
use ValueError;

/**
 * @api This class is used for the {@see ContentSubgraphInterface} ordering
 */
final class OrderingField implements JsonSerializable
{
    private function __construct(
        public readonly PropertyName|TimestampField $field,
        public readonly OrderingDirection $direction,
    ) {
    }

    public static function byProperty(PropertyName $propertyName, OrderingDirection $direction): self
    {
        return new self($propertyName, $direction);
    }

    public static function byTimestampField(TimestampField $timestampField, OrderingDirection $direction): self
    {
        return new self($timestampField, $direction);
    }

    /**
     * @param array<string, mixed> $array
     */
    public static function fromArray(array $array): self
    {
        if (!isset($array['type']) || !in_array($array['type'], ['propertyName', 'timestampField'], true)) {
            throw new InvalidArgumentException(sprintf('Array element "type" must be a string of "propertyName" or "timestampField", given: %s', is_string($array['type'] ?? null) ? $array['type'] : get_debug_type($array['type'] ?? null)), 1680269899);
        }
        /** @var 'propertyName'|'timestampField' $type */
        $type = $array['type'];
        unset($array['type']);
        if (!isset($array['field'])) {
            throw new InvalidArgumentException('Missing array element "field"', 1680270037);
        }
        if ($type === 'propertyName') {
            $field = PropertyName::fromString($array['field']);
        } else {
            try {
                $field = TimestampField::from($array['field']);
            } catch (ValueError $e) {
                throw new InvalidArgumentException(sprintf('Invalid element "field" value: %s', $e->getMessage()), 1680270950, $e);
            }
        }
        unset($array['field']);
        if (!isset($array['direction'])) {
            throw new InvalidArgumentException('Missing array element "direction"', 1680270131);
        }
        try {
            $direction = OrderingDirection::from($array['direction']);
        } catch (ValueError $e) {
            throw new InvalidArgumentException(sprintf('Invalid element "direction" value: %s', $e->getMessage()), 1680271002, $e);
        }
        unset($array['direction']);
        if ($array !== []) {
            throw new InvalidArgumentException(sprintf('Unsupported OrderingField array key%s: "%s"', count($array) === 1 ? '' : 's', implode('", "', array_keys($array))), 1680270194);
        }
        return new self($field, $direction);
    }

    /**
     * @return array{type: 'propertyName'|'timestampField', field: mixed, direction: 'ASCENDING'|'DESCENDING'}
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->field instanceof PropertyName ? 'propertyName' : 'timestampField',
            'field' => $this->field->value,
            'direction' => $this->direction->value,
        ];
    }
}
