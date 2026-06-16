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
    /** @param list<Parameter> $items */
    private function __construct(
        private array $items
    ) {
    }

    public static function create(Parameter ...$items): self
    {
        return new self(array_values($items));
    }

    public function getReference(string $name): string
    {
        foreach ($this->items as $parameter) {
            if ($parameter->name === $name) {
                return ":$name";
            }
        }
        throw new \RuntimeException(sprintf('No parameter exists for %s', $name), 1781593395);
    }

    /**
     * @return array<string, mixed>
     */
    public function toDbalParams(): array
    {
        $values = [];
        foreach ($this->items as $parameter) {
            $values[$parameter->name] = $parameter->value;
        }
        return $values;
    }

    /**
     * @return array<string, int|string|Type|null>
     */
    public function toDbalTypes(): array
    {
        $types = [];
        foreach ($this->items as $parameter) {
            $types[$parameter->name] = $parameter->type;
        }
        return $types;
    }

    public function getIterator(): \Traversable
    {
        yield from $this->items;
    }

    public function count(): int
    {
        return count($this->items);
    }
}
