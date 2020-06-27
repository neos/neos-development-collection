<?php
declare(strict_types=1);

namespace Neos\EventSourcedContentRepository\Domain\Projection\Content;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Projection\Content\PropertyCollectionInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyValues;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Property\PropertyMapper;
use Neos\Flow\Annotations as Flow;

/**
 * The property collection implementation
 *
 * @package Neos\EventSourcedContentRepository
 *
 * @todo iterate over resolved properties
 */
final class PropertyCollection implements PropertyCollectionInterface
{
    /**
     * Properties from Nodes
     *
     * @var PropertyValues
     */
    protected $properties;

    /**
     * @var array
     */
    protected $resolvedPropertyObjects;

    /**
     * @var \ArrayIterator
     */
    protected $iterator;

    /**
     * @Flow\Inject
     * @var PropertyMapper
     */
    protected $propertyMapper;

    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    public function __construct(PropertyValues $properties)
    {
        $this->properties = $properties;
        $this->iterator = new \ArrayIterator($properties->getPlainValues());
    }

    public function offsetExists($offset)
    {
        return $this->properties->propertyExists($offset);
    }

    public function offsetGet($offset)
    {
        $property = $this->properties->getProperty($offset);
        if ($property === null) {
            return null;
        }
        $value = $property->getValue();
        if (is_array($value) && isset($value['__flow_object_type'])) {
            if (!isset($this->resolvedPropertyObjects[$offset])) {
                $this->resolvedPropertyObjects[$offset] = $this->persistenceManager->getObjectByIdentifier($value['__identifier'], $value['__flow_object_type']);
            }
            return $this->resolvedPropertyObjects[$offset];
        }

        return $value;
    }

    public function offsetSet($offset, $value)
    {
        throw new \RuntimeException("Do not use!");
    }

    public function offsetUnset($offset)
    {
        throw new \RuntimeException("Do not use!");
    }

    public function getIterator()
    {
        return $this->iterator;
    }
}
