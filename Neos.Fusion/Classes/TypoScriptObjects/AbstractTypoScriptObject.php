<?php
namespace Neos\Fusion\TypoScriptObjects;

/*
 * This file is part of the Neos.Fusion package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\Flow\Annotations as Flow;
use Neos\Fusion\Core\Runtime;

/**
 * Base class for all TypoScript objects
 */
abstract class AbstractTypoScriptObject implements \ArrayAccess
{
    /**
     * @var Runtime
     */
    protected $tsRuntime;

    /**
     * The TypoScript path currently being rendered
     *
     * @var string
     */
    protected $path;

    /**
     * Name of this TypoScript object, like Neos.Neos:Text
     *
     * @var string
     */
    protected $typoScriptObjectName;

    /**
     * @var array
     */
    protected $tsValueCache = [];

    /**
     * Constructor
     *
     * @param Runtime $tsRuntime
     * @param string $path
     * @param string $typoScriptObjectName
     */
    public function __construct(Runtime $tsRuntime, $path, $typoScriptObjectName)
    {
        $this->tsRuntime = $tsRuntime;
        $this->path = $path;
        $this->typoScriptObjectName = $typoScriptObjectName;
    }

    /**
     * Evaluate this TypoScript object and return the result
     *
     * @return mixed
     */
    abstract public function evaluate();

    /**
     * Get the TypoScript runtime this object was created in.
     *
     * @return Runtime
     */
    public function getTsRuntime()
    {
        return $this->tsRuntime;
    }

    /**
     * Return the TypoScript value relative to this TypoScript object (with processors etc applied).
     *
     * Note that subsequent calls of tsValue() with the same TypoScript path will return the same values since the
     * first evaluated value will be cached in memory.
     *
     * @param string $path
     * @return mixed
     */
    protected function tsValue($path)
    {
        $fullPath = $this->path . '/' . $path;
        if (!isset($this->tsValueCache[$fullPath])) {
            $this->tsValueCache[$fullPath] = $this->tsRuntime->evaluate($fullPath, $this);
        }
        return $this->tsValueCache[$fullPath];
    }

    /**
     * Dummy implementation of ArrayAccess to allow this.XXX access in processors.
     *
     * @param mixed $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return ($this->tsValue($offset) !== null);
    }

    /**
     * Dummy implementation of ArrayAccess to allow this.XXX access in processors.
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->tsValue($offset);
    }

    /**
     * Dummy implementation of ArrayAccess to allow this.XXX access in processors.
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        // no op
    }

    /**
     * Dummy implementation of ArrayAccess to allow this.XXX access in processors.
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        // no op
    }
}
