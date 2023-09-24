<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\Infrastructure\Property\PropertyConverter;

/**
 * The property collection that provides access to the serialized and deserialized properties of a node
 *
 * @implements \ArrayAccess<string,mixed>
 * @implements \IteratorAggregate<string,mixed>
 * @api This object should not be instantiated by 3rd parties, but it is part of the {@see Node} read model
 */
final class PropertyCollection implements \ArrayAccess, \IteratorAggregate
{
    /**
     * Properties from Nodes
     */
    private SerializedPropertyValues $serializedPropertyValues;

    /**
     * @var array<string,mixed>
     */
    private array $deserializedPropertyValuesRuntimeCache = [];

    private PropertyConverter $propertyConverter;

    /**
     * @internal do not create from userspace
     */
    public function __construct(
        SerializedPropertyValues $serializedPropertyValues,
        PropertyConverter $propertyConverter
    ) {
        $this->serializedPropertyValues = $serializedPropertyValues;
        $this->propertyConverter = $propertyConverter;
    }

    public function offsetExists($offset): bool
    {
        return $this->serializedPropertyValues->propertyExists($offset);
    }

    public function offsetGet($offset): mixed
    {
        if (!isset($this->deserializedPropertyValuesRuntimeCache[$offset])) {
            $serializedProperty = $this->serializedPropertyValues->getProperty($offset);
            $this->deserializedPropertyValuesRuntimeCache[$offset] = $serializedProperty === null
                ? null
                : $this->propertyConverter->deserializePropertyValue($serializedProperty);
        }

        return $this->deserializedPropertyValuesRuntimeCache[$offset];
    }

    public function offsetSet($offset, $value): never
    {
        throw new \RuntimeException("Do not use!");
    }

    public function offsetUnset($offset): never
    {
        throw new \RuntimeException("Do not use!");
    }

    /**
     * @return \Generator<string,mixed>
     */
    public function getIterator(): \Generator
    {
        foreach ($this->serializedPropertyValues as $propertyName => $_) {
            yield $propertyName => $this->offsetGet($propertyName);
        }
    }

    public function serialized(): SerializedPropertyValues
    {
        return $this->serializedPropertyValues;
    }
}
