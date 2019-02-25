<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\ValueObject;

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

/**
 * @Flow\Proxy(false)
 */
final class PropertyValues implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * @var array|PropertyValue[]
     */
    private $values = [];

    /**
     * @var \ArrayIterator
     */
    protected $iterator;

    private function __construct(array $values)
    {
        $this->values = $values;
        $this->iterator = new \ArrayIterator($this->values);
    }

    public function merge(PropertyValues $other): PropertyValues
    {
        return new PropertyValues(array_merge($this->values, $other->getValues()));
    }

    /**
     * @return array|PropertyValue[]
     * @param PropertyValue[] values
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public static function fromArray(array $propertyValues): self
    {
        $values = [];
        foreach ($propertyValues as $propertyName => $propertyValue) {
            if (is_array($propertyValue)) {
                $values[$propertyName] = PropertyValue::fromArray($propertyValue);
            } elseif ($propertyValue instanceof PropertyValue) {
                $values[$propertyName] = $propertyValue;
            } else {
                throw new \InvalidArgumentException(sprintf('Invalid property value. Expected instance of %s, got: %s', PropertyValue::class, is_object($propertyValue) ? get_class($propertyValue) : gettype($propertyValue)), 1546524480);
            }
        }
        return new static($values);
    }

    /**
     * @return PropertyValue[]|\ArrayIterator<PropertyValue>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->values);
    }

    public function count(): int
    {
        return count($this->values);
    }

    public function jsonSerialize(): array
    {
        return $this->values;
    }
}
