<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Dbal\Query;

use Doctrine\DBAL\Types\Type;

/**
 * @internal
 * @implements \IteratorAggregate<int,Parameter>
 */
final readonly class Parameters implements \IteratorAggregate, \Countable
{
    /** @param array<string,Parameter> $items */
    private function __construct(
        private array $items
    ) {
    }

    public static function create(Parameter ...$items): self
    {
        return new self(
            array_column($items, null, 'name')
        );
    }

    public function getReference(string $name): string
    {
        $parameter = $this->items[$name] ?? null;
        if ($parameter === null) {
            throw new \RuntimeException(sprintf('No parameter exists for %s', $name), 1781593395);
        }
        return ":{$parameter->name}";
    }

    public function get(string $name): ?Parameter
    {
        return $this->items[$name] ?? null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toDbalValues(): array
    {
        return array_column($this->items, 'value', 'name');
    }

    /**
     * @return array<string, int|string|Type|null>
     */
    public function toDbalTypes(): array
    {
        return array_column($this->items, 'type', 'name');
    }

    /**
     * @param array<string|int, int|string|Type|null> $existingTypes
     * @param array<string|int, int|string|Type|null> $otherTypes
     * @return array<string|int, int|string|Type|null>
     */
    public static function mergeDbalTypes(array $existingTypes, array $otherTypes): array
    {
        $intersectingExistingTypes = array_intersect_key($existingTypes, $otherTypes);

        foreach ($intersectingExistingTypes as $existingKey => $existingType) {
            $otherType = $otherTypes[$existingKey];
            if ($otherType !== $existingType) {
                throw AmbiguousParametersGiven::becauseParameterIsAlreadyDefinedWithType(
                    (string)$existingKey,
                    $existingType,
                    $otherType
                );
            }
        }

        return array_merge($existingTypes, $otherTypes);
    }

    /**
     * @param array<string|int, mixed> $existingValues
     * @param array<string|int, mixed> $otherValues
     * @return array<string|int, mixed>
     */
    public static function mergeDbalValues(array $existingValues, array $otherValues): array
    {
        $intersectingExistingValues = array_intersect_key($existingValues, $otherValues);

        foreach ($intersectingExistingValues as $existingKey => $existingValue) {
            $otherValue = $otherValues[$existingKey];
            if ($otherValue !== $existingValue) {
                throw AmbiguousParametersGiven::becauseParameterIsAlreadyDefinedWithValue(
                    (string)$existingKey,
                    $existingValue,
                    $otherValue
                );
            }
        }

        return array_merge($existingValues, $otherValues);
    }

    public function getIterator(): \Traversable
    {
        yield from array_values($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
