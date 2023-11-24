<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter;

use Neos\ContentRepository\Core\Projection\ContentGraph\Filter\NodeType\NodeTypeCriteria;

/**
 * Immutable filter DTO for {@see ContentSubgraphInterface::findSubtree()}
 *
 * Example:
 *
 * FindSubtreeFilter::create(nodeTypes: 'Some.Included:NodeType,!Some.Excluded:NodeType');
 *
 * @api for the factory methods; NOT for the inner state.
 */
final class FindSubtreeFilter
{
    /**
     * @internal (the properties themselves are readonly; only the write-methods are API.
     */
    private function __construct(
        public readonly ?NodeTypeCriteria $nodeTypes,
        public readonly ?int $maximumLevels,
    ) {
    }

    /**
     * Creates an instance with the specified filter options
     *
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     */
    public static function create(
        NodeTypeCriteria|string $nodeTypes = null,
        int $maximumLevels = null,
    ): self {
        if (is_string($nodeTypes)) {
            $nodeTypes = NodeTypeCriteria::fromFilterString($nodeTypes);
        }
        return new self($nodeTypes, $maximumLevels);
    }

    /**
     * Returns a new instance with the specified additional filter options
     *
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     */
    public function with(
        NodeTypeCriteria|string $nodeTypes = null,
        int $maximumLevels = null,
    ): self {
        return self::create(
            $nodeTypes ?? $this->nodeTypes,
            $maximumLevels ?? $this->maximumLevels,
        );
    }
}
