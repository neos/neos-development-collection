<?php
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

final class PropertyValues implements \JsonSerializable, \IteratorAggregate
{
    /**
     * @var array|PropertyValue[]
     */
    private $values;

    /**
     * @var \ArrayIterator
     */
    protected $iterator;

    /**
     * @param array|PropertyValue[] $values
     */
    public function __construct(array $values)
    {
        foreach ($values as $propertyName => $propertyValue) {
            $propertyName = new PropertyName($propertyName);
            if (!$propertyValue instanceof PropertyValue) {
                throw new \InvalidArgumentException('PropertyValues objects can only be composed of PropertyValue objects.');
            }
            $this->values[(string) $propertyName] = $propertyValue;
        }
        $this->iterator = new \ArrayIterator($this->values);
    }

    public static function jsonUnserialize(array $jsonArray): PropertyValues
    {
        return new PropertyValues($jsonArray);
    }

    public function merge(PropertyValues $other): PropertyValues
    {
        return new PropertyValues(array_merge($this->values, $other->getValues()));
    }

    /**
     * @return array|PropertyValue[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public function jsonSerialize(): array
    {
        return $this->values;
    }

    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }
}
