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
 * FindRootNodeAggregatesFilter::create()->with(nodeTypeName: $nodeTypeName);
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

    public static function create(): self
    {
        return new self(null);
    }

    public static function nodeTypeName(NodeTypeName $nodeTypeName): self
    {
        return self::create()->with(nodeTypeName: $nodeTypeName);
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
        return new self(
            $nodeTypeName ?? $this->nodeTypeName,
        );
    }
}
