<?php

namespace Neos\Fusion\FusionObjects\Helpers;

use Neos\Flow\Annotations as Flow;

/** @Flow\Proxy(false) */
abstract class AbstractLazyProps implements \ArrayAccess, \Iterator, \JsonSerializable
{
    private array $keyIndexes;

    public function __construct(
        array $keys,
    ) {
        $this->keyIndexes = array_flip($keys);
    }

    abstract public function offsetGet($path): mixed;

    public function offsetExists($path): bool
    {
        return array_key_exists($path, $this->keyIndexes);
    }

    public function offsetSet($path, $value): void
    {
        throw new \BadMethodCallException('Lazy props can not be set.', 1588182804);
    }

    public function offsetUnset($path): void
    {
        throw new \BadMethodCallException('Lazy props can not be unset.', 1588182805);
    }

    public function current(): mixed
    {
        $path = key($this->keyIndexes);
        if ($path === null) {
            return null;
        }
        return $this->offsetGet($path);
    }

    public function next(): void
    {
        next($this->keyIndexes);
    }

    public function key(): mixed
    {
        return key($this->keyIndexes);
    }

    public function valid(): bool
    {
        return current($this->keyIndexes) !== false;
    }

    public function rewind(): void
    {
        reset($this->keyIndexes);
    }

    public function jsonSerialize(): mixed
    {
        return iterator_to_array($this);
    }
}
