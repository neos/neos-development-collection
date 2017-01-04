<?php
namespace Neos\Fusion\FusionObjects;

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
 * Base class for all Fusion objects
 */
abstract class AbstractFusionObject implements \ArrayAccess
{
    /**
     * @var Runtime
     */
    protected $tsRuntime;

    /**
     * The Fusion path currently being rendered
     *
     * @var string
     */
    protected $path;

    /**
     * Name of this Fusion object, like Neos.Neos:Text
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
     * Evaluate this Fusion object and return the result
     *
     * @return mixed
     */
    abstract public function evaluate();

    /**
     * Get the Fusion runtime this object was created in.
     *
     * @return Runtime
     */
    public function getTsRuntime()
    {
        return $this->tsRuntime;
    }

    /**
     * Return the Fusion value relative to this Fusion object (with processors etc applied).
     *
     * Note that subsequent calls of tsValue() with the same Fusion path will return the same values since the
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
