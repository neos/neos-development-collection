<?php
namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate;

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
use Neos\ContentRepository\DimensionSpace\DimensionSpace\DimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\Exception\NodeVariantAssignmentCollectionIsInvalid;

/**
 * An immutable collection of node variant assignments, indexed by (covered, not origin) dimension space point
 *
 * @Flow\Proxy(false)
 */
final class NodeVariantAssignments implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @var array|NodeVariantAssignment[]
     */
    private $assignments;

    /**
     * @var \ArrayIterator
     */
    private $iterator;

    private function __construct(array $assignments)
    {
        $this->assignments = $assignments;
        $this->iterator = new \ArrayIterator($this->assignments);
    }

    public static function createFromArray(array $array): self
    {
        foreach ($array as &$item) {
            if (is_array($item)) {
                $item = NodeVariantAssignment::createFromArray($item);
            }
            if (!$item instanceof NodeVariantAssignment) {
                throw NodeVariantAssignmentCollectionIsInvalid::becauseItContainsSomethingOther();
            }
        }
        return new self($array);
    }

    public static function create(): self
    {
        return new self([]);
    }

    public function add(NodeVariantAssignment $assignment, DimensionSpacePoint $dimensionSpacePoint): self
    {
        $assignments = $this->assignments;
        $assignments[$dimensionSpacePoint->hash] = $assignment;

        return new self($assignments);
    }

    public function get(DimensionSpacePoint $dimensionSpacePoint): ?NodeVariantAssignment
    {
        return $this->assignments[$dimensionSpacePoint->hash] ?? null;
    }

    /**
     * @return array|NodeVariantAssignment[]
     */
    public function getAssignments(): array
    {
        return $this->assignments;
    }

    public function isEmpty(): bool
    {
        return empty($this->assignments);
    }

    /**
     * @return \ArrayIterator|NodeVariantAssignment[]
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }

    public function jsonSerialize(): array
    {
        return $this->assignments;
    }
}
