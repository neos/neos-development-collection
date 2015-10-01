<?php
namespace TYPO3\TypoScript\TypoScriptObjects;

/*                                                                        *
 * This script belongs to the TYPO3 Flow package "TypoScript".            *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License, either version 3 of the   *
 * License, or (at your option) any later version.                        *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

use TYPO3\Flow\Annotations as Flow;

/**
 * Base class for all TypoScript objects
 */
abstract class AbstractTypoScriptObject implements \ArrayAccess
{
    /**
     * @var \TYPO3\TypoScript\Core\Runtime
     */
    protected $tsRuntime;

    /**
     * The TypoScript path currently being rendered
     *
     * @var string
     */
    protected $path;

    /**
     * Name of this TypoScript object, like TYPO3.Neos:Text
     *
     * @var string
     */
    protected $typoScriptObjectName;

    /**
     * @var array
     */
    protected $tsValueCache = array();

    /**
     * Constructor
     *
     * @param \TYPO3\TypoScript\Core\Runtime $tsRuntime
     * @param string $path
     * @param string $typoScriptObjectName
     */
    public function __construct(\TYPO3\TypoScript\Core\Runtime $tsRuntime, $path, $typoScriptObjectName)
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
