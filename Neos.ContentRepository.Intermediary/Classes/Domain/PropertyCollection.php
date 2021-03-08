<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Intermediary\Domain;

/*
 * This file is part of the Neos.ContentRepository.Intermediary package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Projection\Content\PropertyCollectionInterface;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;

/**
 * The property collection implementation
 */
final class PropertyCollection implements PropertyCollectionInterface
{
    /**
     * Properties from Nodes
     */
    private SerializedPropertyValues $serializedPropertyValues;

    private array $deserializedPropertyValues;

    private \ArrayIterator $iterator;

    private PropertyConverter $propertyConversionService;

    /**
     * @internal do not create from userspace
     */
    public function __construct(SerializedPropertyValues $serializedPropertyValues, PropertyConverter $propertyConversionService)
    {
        $this->serializedPropertyValues = $serializedPropertyValues;
        $this->iterator = new \ArrayIterator($serializedPropertyValues->getPlainValues());
        $this->propertyConversionService = $propertyConversionService;
    }

    public function offsetExists($offset): bool
    {
        return $this->serializedPropertyValues->propertyExists($offset);
    }

    /**
     * @return mixed|null
     */
    public function offsetGet($offset)
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }
        if (!isset($this->deserializedPropertyValues[$offset])) {
            $this->deserializedPropertyValues[$offset] = $this->propertyConversionService->deserializePropertyValue(
                $this->serializedPropertyValues->getProperty($offset)
            );
        }

        return $this->deserializedPropertyValues[$offset];
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

    public function getSerializedPropertyValues(): SerializedPropertyValues
    {
        return $this->serializedPropertyValues;
    }
}
