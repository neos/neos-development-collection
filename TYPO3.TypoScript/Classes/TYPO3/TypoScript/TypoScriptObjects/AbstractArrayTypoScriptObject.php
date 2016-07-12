<?php
namespace TYPO3\TypoScript\TypoScriptObjects;

/*
 * This file is part of the TYPO3.TypoScript package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

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
     * If you iterate over "properties" these in here should usually be ignored. For example additional properties in "Case" that are not "Matchers".
     *
     * @var array
     */
    protected $ignoreProperties = array();

    /**
     * @param array $ignoreProperties
     * @return void
     */
    public function setIgnoreProperties($ignoreProperties = array())
    {
        $this->ignoreProperties = $ignoreProperties;
    }

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
