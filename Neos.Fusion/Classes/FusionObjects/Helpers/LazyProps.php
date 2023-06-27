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

    #[\ReturnTypeWillChange]
    public function offsetExists($path)
    {
        return array_key_exists($path, $this->keys);
    }

    #[\ReturnTypeWillChange]
    public function offsetGet($path)
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

    #[\ReturnTypeWillChange]
    public function offsetSet($path, $value)
    {
        throw new BadMethodCallException('Lazy props can not be set.', 1588182804);
    }

    #[\ReturnTypeWillChange]
    public function offsetUnset($path)
    {
        throw new BadMethodCallException('Lazy props can not be unset.', 1588182805);
    }

    #[\ReturnTypeWillChange]
    public function current()
    {
        $path = key($this->keys);
        if ($path === null) {
            return null;
        }
        return $this->offsetGet($path);
    }

    #[\ReturnTypeWillChange]
    public function next()
    {
        next($this->keys);
    }

    #[\ReturnTypeWillChange]
    public function key()
    {
        return key($this->keys);
    }

    #[\ReturnTypeWillChange]
    public function valid()
    {
        return current($this->keys) !== false;
    }

    #[\ReturnTypeWillChange]
    public function rewind()
    {
        reset($this->keys);
    }

    #[\ReturnTypeWillChange]
    public function jsonSerialize()
    {
        return iterator_to_array($this);
    }
}
