<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter;

use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;

/**
 * @api for the factory methods; NOT for the inner state.
 */
final class FindSubtreesFilter
{
    /**
     * @internal (the properties themselves are readonly; only the write-methods are API.
     */
    private function __construct(
        public readonly ?NodeTypeConstraints $nodeTypeConstraints,
        public readonly ?int $maximumLevels,
    ) {
    }

    public static function all(): self
    {
        return new self(null, null, null);
    }

    public static function nodeTypeConstraints(NodeTypeConstraints|string $nodeTypeConstraints): self
    {
        return self::all()->with(nodeTypeConstraints: $nodeTypeConstraints);
    }

    public function with(
        NodeTypeConstraints|string $nodeTypeConstraints = null,
        int $maximumLevels = null,
    ): self {
        if (is_string($nodeTypeConstraints)) {
            $nodeTypeConstraints = NodeTypeConstraints::fromFilterString($nodeTypeConstraints);
        }
        return new self(
            $nodeTypeConstraints ?? $this->nodeTypeConstraints,
            $maximumLevels ?? $this->maximumLevels,
        );
    }

    public function withMaximumLevels(int $maximumLevels): self
    {
        return $this->with(maximumLevels: $maximumLevels);
    }
}
