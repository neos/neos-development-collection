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
final class NodeReferences implements \IteratorAggregate, \Countable, \JsonSerializable
{
    /**
     * @var array|NodeReference[]
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

    public function merge(NodeReferences $other): NodeReferences
    {
        return new self(array_merge($this->values, $other->getValues()));
    }

    /**
     * @param SerializedPropertyValue[] values
     *@return array|SerializedPropertyValue[]
     */
    public function getValues(): array
    {
        return $this->values;
    }

    public static function fromArray(array $nodeReferences): self
    {
        $values = [];
        foreach ($nodeReferences as $nodeReferenceName => $nodeReferenceValue) {
            if (is_array($nodeReferenceValue)) {
                $values[$nodeReferenceName] = NodeReference::fromArray($nodeReferenceValue);
            } elseif ($nodeReferenceValue instanceof NodeReference) {
                $values[$nodeReferenceName] = $nodeReferenceValue;
            } else {
                throw new \InvalidArgumentException(sprintf('Invalid nodeReferences value. Expected instance of %s, got: %s', NodeReference::class, is_object($nodeReferenceValue) ? get_class($nodeReferenceValue) : gettype($nodeReferenceValue)), 1546524480);
            }
        }

        return new static($values);
    }

    /**
     * @return SerializedPropertyValue[]|\ArrayIterator<SerializedPropertyValue>
     */
    public function getIterator(): \ArrayIterator
    {
        return new \ArrayIterator($this->values);
    }

    public function count(): int
    {
        return count($this->values);
    }

    /*public function getPlainValues(): array
    {
        $values = [];
        foreach ($this->values as $propertyName => $propertyValue) {
            $values[$propertyName] = $propertyValue->getValue();
        }

        return $values;
    }*/

    public function jsonSerialize(): array
    {
        return $this->values;
    }
}
