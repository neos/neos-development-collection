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
 * Base class for TypoScript objects that need access to arbitrary properties, like ArrayImplementation.
 */
abstract class AbstractArrayTypoScriptObject extends AbstractTypoScriptObject implements \ArrayAccess
{
    /**
     * List of properties which have been set using array access. We store this for *every* TypoScript object
     * in order to do things like:
     * x = Foo {
     *   a = 'foo'
     *   b = ${this.a + 'bar'}
     * }
     *
     * @var array
     * @internal
     */
    protected $properties = array();

    /**
     * @param mixed $offset
     * @return boolean
     */
    public function offsetExists($offset)
    {
        return isset($this->properties[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->tsValue($offset);
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet($offset, $value)
    {
        $this->properties[$offset] = $value;
    }

    /**
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset($offset)
    {
        unset($this->properties[$offset]);
    }
}
