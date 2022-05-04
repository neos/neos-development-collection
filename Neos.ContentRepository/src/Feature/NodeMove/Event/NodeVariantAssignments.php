<?php
namespace Neos\ContentRepository\Feature\NodeMove\Event;

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
use Neos\ContentRepository\Feature\NodeMove\Exception\NodeVariantAssignmentCollectionIsInvalid;

/**
 * An immutable collection of node variant assignments, indexed by (covered, not origin) dimension space point hash
 * @implements \IteratorAggregate<string,NodeVariantAssignment>
 */
#[Flow\Proxy(false)]
final class NodeVariantAssignments implements \IteratorAggregate, \JsonSerializable
{
    /**
     * @var array<string,NodeVariantAssignment>
     */
    private array $assignments;

    /**
     * @var \ArrayIterator<string,NodeVariantAssignment>
     */
    private \ArrayIterator $iterator;

    /**
     * @param array<string,NodeVariantAssignment> $assignments
     */
    private function __construct(array $assignments)
    {
        $this->assignments = $assignments;
        $this->iterator = new \ArrayIterator($this->assignments);
    }

    /**
     * @param array<string,array<string,mixed>|NodeVariantAssignment> $array
     */
    public static function createFromArray(array $array): self
    {
        $assignments = [];
        foreach ($array as $key => &$item) {
            if (is_array($item)) {
                $item = NodeVariantAssignment::createFromArray($item);
            }
            if (!$item instanceof NodeVariantAssignment) {
                throw NodeVariantAssignmentCollectionIsInvalid::becauseItContainsSomethingOther();
            }
            $assignments[$key] = $item;
        }
        return new self($assignments);
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
     * @return array<string,NodeVariantAssignment>
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
     * @return \ArrayIterator<string,NodeVariantAssignment>
     */
    public function getIterator(): \ArrayIterator
    {
        return $this->iterator;
    }

    /**
     * @return array<string,NodeVariantAssignment>
     */
    public function jsonSerialize(): array
    {
        return $this->assignments;
    }
}
