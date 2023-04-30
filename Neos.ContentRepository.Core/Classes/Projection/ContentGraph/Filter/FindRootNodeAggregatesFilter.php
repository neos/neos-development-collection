<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter;

use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;

/**
 * Immutable filter DTO for {@see ContentGraphInterface::findRootNodeAggregates()}
 *
 * Example:
 *
 * FindRootNodeAggregatesFilter::create(nodeTypeName: $nodeTypeName);
 *
 * @api for the factory methods; NOT for the inner state.
 */
final class FindRootNodeAggregatesFilter
{
    /**
     * @internal (the properties themselves are readonly; only the write-methods are API)
     */
    private function __construct(
        public readonly ?NodeTypeName $nodeTypeName,
    ) {
    }

    /**
     * Creates an instance with the specified filter options
     *
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     */
    public static function create(
        NodeTypeName|string $nodeTypeName = null,
    ): self {
        if (is_string($nodeTypeName)) {
            $nodeTypeName = NodeTypeName::fromString($nodeTypeName);
        }
        return new self($nodeTypeName);
    }

    /**
     * Returns a new instance with the specified additional filter options
     *
     * Note: The signature of this method might be extended in the future, so it should always be used with named arguments
     * @see https://www.php.net/manual/en/functions.arguments.php#functions.named-arguments
     */
    public function with(
        NodeTypeName $nodeTypeName = null,
    ): self {
        return self::create(
            $nodeTypeName ?? $this->nodeTypeName,
        );
    }
}
