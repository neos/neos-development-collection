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
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Property\PropertyConversionService;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;

/**
 * The property collection implementation
 *
 * @package Neos\EventSourcedContentRepository
 */
final class PropertyCollection implements PropertyCollectionInterface
{
    /**
     * Properties from Nodes
     *
     * @var SerializedPropertyValues
     */
    protected $serializedPropertyValues;

    /**
     * @var \ArrayIterator
     */
    protected $iterator;

    protected PropertyConversionService $propertyConversionService;

    /**
     * @internal do not create from userspace
     */
    public function __construct(SerializedPropertyValues $serializedPropertyValues, PropertyConversionService $propertyConversionService)
    {
        $this->serializedPropertyValues = $serializedPropertyValues;
        $this->iterator = new \ArrayIterator($serializedPropertyValues->getPlainValues());
        $this->propertyConversionService = $propertyConversionService;
    }

    public function offsetExists($offset)
    {
        return $this->serializedPropertyValues->propertyExists($offset);
    }

    public function offsetGet($offset)
    {
        $property = $this->serializedPropertyValues->getProperty($offset);
        if ($property === null) {
            return null;
        }
        return $this->propertyConversionService->deserializePropertyValue($property);
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

    /**
     * @return SerializedPropertyValues
     */
    public function getSerializedPropertyValues(): SerializedPropertyValues
    {
        return $this->serializedPropertyValues;
    }
}
