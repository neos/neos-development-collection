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
final class NodeReference
{
    // TODO: actually working??


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

    /**
     * @param SerializedPropertyValue[] values
     *@return array|SerializedPropertyValue[]
     */
    public function getValues(): array
    {
        return $this->values;
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

    public function jsonSerialize(): array
    {
        return $this->values;
    }
}
