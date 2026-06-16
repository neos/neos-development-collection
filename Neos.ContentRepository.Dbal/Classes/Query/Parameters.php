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
        /** @var array<string,Parameter> $indexed */
        $indexed = [];
        foreach ($items as $parameter) {
            if (!array_key_exists($parameter->name, $indexed)) {
                $indexed[$parameter->name] = $parameter;
                continue;
            }
            $existing = $indexed[$parameter->name];

            if ($existing->type !== $parameter->type) {
                throw AmbiguousParametersGiven::becauseParameterIsAlreadyDefinedWithType(
                    $existing->name,
                    $existing->type,
                    $parameter->type
                );
            }

            if ($existing->value !== $parameter->value) {
                throw AmbiguousParametersGiven::becauseParameterIsAlreadyDefinedWithValue(
                    $existing->name,
                    $existing->value,
                    $parameter->value
                );
            }
        }

        return new self(
            $indexed
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

    public function getIterator(): \Traversable
    {
        yield from array_values($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
