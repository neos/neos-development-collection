<?php

namespace Neos\Fusion\FusionObjects\Helpers;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\Runtime;

/**
 * @Flow\Proxy(false)
 */
final class LazyProps implements \ArrayAccess, \Iterator
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

    public function offsetExists($path)
    {
        return isset($this->keys[$path]);
    }

    public function offsetGet($path)
    {
        if (!isset($this->valueCache[$path])) {
            $this->runtime->pushContextArray($this->effectiveContext);
            try {
                $this->valueCache[$path] = $this->runtime->evaluate($this->parentPath . '/' . $path,
                    $this->fusionObject);
            } finally {
                $this->runtime->popContext();
            }
        }
        return $this->valueCache[$path];
    }

    public function offsetSet($path, $value)
    {
        // NOOP
    }

    public function offsetUnset($path)
    {
        // NOOP
    }

    public function current()
    {
        $path = key($this->keys);
        if ($path === null) {
            return null;
        }
        return $this->offsetGet($path);
    }

    public function next()
    {
        next($this->keys);
    }

    public function key()
    {
        return key($this->keys);
    }

    public function valid()
    {
        return current($this->keys) !== false;
    }

    public function rewind()
    {
        reset($this->keys);
    }
}
