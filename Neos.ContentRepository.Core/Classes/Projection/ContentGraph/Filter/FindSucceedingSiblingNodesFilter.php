<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter;

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\Pagination\Pagination;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;

/**
 * Immutable filter DTO for {@see ContentSubgraphInterface::findSucceedingSiblingNodes()}
 *
 * Example:
 *
 * FindSucceedingSiblingNodesFilter::create()->with(nodeTypeConstraint: 'Some.Included:NodeType,!Some.Excluded:NodeType');
 *
 * @api for the factory methods; NOT for the inner state.
 */
final class FindSucceedingSiblingNodesFilter
{
    /**
     * @internal (the properties themselves are readonly; only the write-methods are API.
     */
    private function __construct(
        public readonly ?NodeTypeConstraints $nodeTypeConstraints,
        public readonly ?Pagination $pagination,
    ) {
    }

    public static function create(): self
    {
        return new self(null, null);
    }

    public static function nodeTypeConstraints(NodeTypeConstraints|string $nodeTypeConstraints): self
    {
        return self::create()->with(nodeTypeConstraints: $nodeTypeConstraints);
    }

    /**
     * Returns a new instance with the specified additional filter options
     *
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     *
     * @param Pagination|array<string, mixed>|null $pagination
     */
    public function with(
        NodeTypeConstraints|string $nodeTypeConstraints = null,
        Pagination|array $pagination = null,
    ): self {
        if (is_string($nodeTypeConstraints)) {
            $nodeTypeConstraints = NodeTypeConstraints::fromFilterString($nodeTypeConstraints);
        }
        if (is_array($pagination)) {
            $pagination = Pagination::fromArray($pagination);
        }
        return new self(
            $nodeTypeConstraints ?? $this->nodeTypeConstraints,
            $pagination ?? $this->pagination,
        );
    }

    public function withPagination(int $limit, int $offset): self
    {
        return $this->with(pagination: Pagination::fromLimitAndOffset($limit, $offset));
    }
}
