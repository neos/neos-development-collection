<?php

namespace Neos\Fusion\FusionObjects\Helpers;

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\Runtime;

/**
 * @Flow\Proxy(false)
 */
final class LazyProps implements \ArrayAccess
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

    public function offsetExists($offset)
    {
        return isset($this->keys[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->fusionValue($offset);
    }

    public function offsetSet($offset, $value)
    {
        // NOOP
    }

    public function offsetUnset($offset)
    {
        // NOOP
    }

    protected function fusionValue(string $path)
    {
        if (!isset($this->valueCache[$path])) {
            $this->runtime->pushContextArray($this->effectiveContext);
            try {
                $this->valueCache[$path] = $this->runtime->evaluate($this->parentPath . '/' . $path, $this->fusionObject);
            } finally {
                $this->runtime->popContext();
            }
        }
        return $this->valueCache[$path];
    }
}
