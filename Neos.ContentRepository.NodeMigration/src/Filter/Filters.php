<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeMigration\Filter;

use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeAggregate;
use Neos\Flow\Annotations as Flow;

#[Flow\Proxy(false)]
final class Filters
{
    /**
     * @var array<int,NodeBasedFilterInterface>
     */
    protected array $nodeBasedFilters = [];

    /**
     * @var array<int,NodeAggregateBasedFilterInterface>
     */
    protected array $nodeAggregateBasedFilters = [];

    /**
     * @param array<int|string,NodeBasedFilterInterface|NodeAggregateBasedFilterInterface> $filterObjects
     */
    public function __construct(array $filterObjects)
    {
        foreach ($filterObjects as $filterObject) {
            if ($filterObject instanceof NodeBasedFilterInterface) {
                $this->nodeBasedFilters[] = $filterObject;
            } elseif ($filterObject instanceof NodeAggregateBasedFilterInterface) {
                $this->nodeAggregateBasedFilters[] = $filterObject;
            } else {
                /** @var mixed $filterObject */
                throw new \InvalidArgumentException(sprintf(
                    'Filter object must implement either %s or %s. Given: %s',
                    NodeBasedFilterInterface::class,
                    NodeAggregateBasedFilterInterface::class,
                    get_debug_type($filterObject)
                ), 1611735521);
            }
        }
    }

    public function containsNodeAggregateBased(): bool
    {
        return count($this->nodeAggregateBasedFilters) > 0;
    }

    public function containsNodeBased(): bool
    {
        return count($this->nodeBasedFilters) > 0;
    }

    public function matchesNodeAggregate(NodeAggregate $nodeAggregate): bool
    {
        return array_all($this->nodeAggregateBasedFilters, fn ($filter) => $filter->matches($nodeAggregate));
    }

    public function matchesNode(Node $node): bool
    {
        return array_all($this->nodeBasedFilters, fn ($filter) => $filter->matches($node));
    }
}
