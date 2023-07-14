<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter;

use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;

/**
 * Immutable filter DTO for {@see ContentSubgraphInterface::countAncestorNodes()}
 *
 * Example:
 *
 * FindAncestorNodesFilter::create(nodeTypeConstraints: 'Some.Included:NodeType,!Some.Excluded:NodeType');
 *
 * @api for the factory methods; NOT for the inner state.
 */
final class CountAncestorNodesFilter
{
    /**
     * @internal (the properties themselves are readonly; only the write-methods are API.
     */
    private function __construct(
        public readonly ?NodeTypeConstraints $nodeTypeConstraints
    ) {
    }

    /**
     * Creates an instance with the specified filter options
     *
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     */
    public static function create(
        NodeTypeConstraints|string $nodeTypeConstraints = null
    ): self {
        if (is_string($nodeTypeConstraints)) {
            $nodeTypeConstraints = NodeTypeConstraints::fromFilterString($nodeTypeConstraints);
        }
        return new self($nodeTypeConstraints);
    }

    public static function fromFindAncestorNodesFilter(FindAncestorNodesFilter $filter): self
    {
        return new self($filter->nodeTypeConstraints);
    }

    /**
     * Returns a new instance with the specified additional filter options
     *
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     */
    public function with(
        NodeTypeConstraints|string $nodeTypeConstraints = null
    ): self {
        return self::create(
            $nodeTypeConstraints ?? $this->nodeTypeConstraints,
        );
    }
}
