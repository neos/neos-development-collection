<?php
declare(strict_types=1);
namespace Neos\ESCR\Export\ValueObject;

use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Proxy(false)
 *
 * @implements \IteratorAggregate<string, string|int|bool|null>
 */
final class Parameters implements \IteratorAggregate
{
    /**
     * @param array<string|int|bool|null> $values
     */
    private function __construct(
        private readonly array $values
    ) {}

    /**
     * @param array<string|int|bool|null> $array
     */
    public static function fromArray(array $array): self
    {
        return new self($array);
    }

    public function with(string $parameterName, string|int|bool|null $value): self
    {
        $values = $this->values;
        $values[$parameterName] = $value;
        return new self($values);
    }

    /**
     * @return \Iterator<string, string|int|bool|null>
     */
    public function getIterator(): \Iterator
    {
        return new \ArrayIterator($this->values);
    }

    public function has(string $name): bool
    {
        return \array_key_exists($name, $this->values);
    }

    public function get(string $name): string|int|bool|null
    {
        if (!$this->has($name)) {
            throw new \InvalidArgumentException(sprintf('Unknown parameter "%s"', $name), 1638381660);
        }
        return $this->values[$name];
    }
}
