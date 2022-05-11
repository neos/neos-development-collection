<?php
namespace Neos\ContentRepository\Tests\Functional\Domain\Fixtures;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

/**
 * A test class that has a property to wrap some value for serialization in Node properties
 */
class TestObjectForSerialization
{
    /**
     * @var object
     */
    protected $value;

    /**
     * @param object $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }

    /**
     * @return object
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return array
     */
    public function __sleep()
    {
        return ['value'];
    }
}
