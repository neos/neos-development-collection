<?php

namespace Neos\Fusion\FusionObjects\Helpers;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\Runtime;

/**
 * @Flow\Proxy(false)
 */
final class LazyProps implements \ArrayAccess, \Iterator, \JsonSerializable
{
    /**
     * @var array
     */
    private $valueCache = [];

    /**
     * @var string
     */
    private $parentPath;

    /**
     * @var Runtime
     */
    private $runtime;

    /**
     * Index of keys
     *
     * @var array
     */
    private $keys;

    /**
     * @var object
     */
    private $fusionObject;

    /**
     * @var array
     */
    private $effectiveContext;

    public function __construct(
        object $fusionObject,
        string $parentPath,
        Runtime $runtime,
        array $keys,
        array $effectiveContext
    ) {
        $this->fusionObject = $fusionObject;
        $this->parentPath = $parentPath;
        $this->runtime = $runtime;
        $this->keys = array_flip($keys);
        $this->effectiveContext = $effectiveContext;
    }

    public function offsetExists($path): bool
    {
        return array_key_exists($path, $this->keys);
    }

    public function offsetGet($path): mixed
    {
        if (!array_key_exists($path, $this->valueCache)) {
            $this->runtime->pushContextArray($this->effectiveContext);
            try {
                $this->valueCache[$path] = $this->runtime->evaluate($this->parentPath . '/' . $path, $this->fusionObject);
            } finally {
                $this->runtime->popContext();
            }
        }
        return $this->valueCache[$path];
    }

    public function offsetSet($path, $value): void
    {
        throw new BadMethodCallException('Lazy props can not be set.', 1588182804);
    }

    public function offsetUnset($path): void
    {
        throw new BadMethodCallException('Lazy props can not be unset.', 1588182805);
    }

    public function current(): mixed
    {
        $path = key($this->keys);
        if ($path === null) {
            return null;
        }
        return $this->offsetGet($path);
    }

    public function next(): void
    {
        next($this->keys);
    }

    public function key(): mixed
    {
        return key($this->keys);
    }

    public function valid(): bool
    {
        return current($this->keys) !== false;
    }

    public function rewind(): void
    {
        reset($this->keys);
    }

    public function jsonSerialize(): mixed
    {
        return iterator_to_array($this);
    }
}
