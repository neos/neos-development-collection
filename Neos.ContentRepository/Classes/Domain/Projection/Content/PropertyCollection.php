<?php

namespace Neos\ContentRepository\Domain\Projection\Content;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;

/**
 * The property collection implementation
 *
 * Takes care of lazily resolving entity properties
 */
class PropertyCollection implements \ArrayAccess, \Iterator
{
    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @var array
     */
    protected $properties;

    /**
     * @var array
     */
    protected $resolvedProperties;


    public function __construct(array $properties)
    {
        $this->properties = $properties;
    }


    public function offsetExists($offset)
    {
        return isset($this->properties[$offset]);
    }

    public function offsetGet($offset)
    {
        if (is_array($this->properties[$offset]) && !isset($this->resolvedProperties[$offset])) {
            if (isset($this->properties[$offset]['__flow_object_type'])) {
                $this->resolveObject($this->properties[$offset]);
            } else {
                foreach ($this->properties[$offset] as $i => $propertyValue) {
                    if (isset($this->properties[$offset][$i]['__flow_object_type'])) {
                        $this->resolveObject($this->properties[$offset][$i]);
                    }
                }
            }
            $this->resolvedProperties[$offset] = true;
        }

        return $this->properties[$offset];
    }

    /**
     * @param $value
     */
    protected function resolveObject(&$value)
    {
        $value = $this->persistenceManager->getObjectByIdentifier(
            $value['__identifier'],
            $value['__flow_object_type']
        );
    }

    public function offsetSet($offset, $value)
    {
        $this->properties[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->properties);
    }

    public function current()
    {
        return $this->offsetGet($this->key());
    }

    public function next()
    {
        next($this->properties);
    }

    public function key()
    {
        return key($this->properties);
    }

    public function valid()
    {
        return current($this->properties) !== false;
    }

    public function rewind()
    {
        reset($this->properties);
    }

    public function toArray(): array
    {
        return $this->properties;
    }

    public function getPropertyNames(): array
    {
        return array_keys($this->properties);
    }
}
