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

/**
 * Base class for Fusion objects that need access to arbitrary properties, like ArrayImplementation.
 */
abstract class AbstractArrayFusionObject extends AbstractFusionObject implements \ArrayAccess
{
    /**
     * List of properties which have been set using array access. We store this for *every* Fusion object
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
        return $this->fusionValue($offset);
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
