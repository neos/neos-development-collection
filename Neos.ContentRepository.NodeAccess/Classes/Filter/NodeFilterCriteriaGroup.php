<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeAccess\Filter;

use Traversable;

/**
 * @implements \IteratorAggregate<int, NodeFilterCriteria>
 */
readonly class NodeFilterCriteriaGroup implements \IteratorAggregate
{
    /**
     * @var array<int, NodeFilterCriteria>
     */
    private array $criteria;

    public function __construct(NodeFilterCriteria ...$criteria)
    {
        $this->criteria = array_values($criteria);
    }

    /**
     * @return Traversable<int, NodeFilterCriteria>
     */
    public function getIterator(): Traversable
    {
        yield from $this->criteria;
    }

}
