<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph\Filter;

use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTypeConstraints;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;

/**
 * @api for the factory methods; NOT for the inner state.
 */
final class FindReferencesFilter
{
    /**
     * @internal (the properties themselves are readonly; only the write-methods are API.
     */
    private function __construct(
        public readonly ?ReferenceName $referenceName,
    ) {
    }

    public static function all(): self
    {
        return new self(null);
    }

    public static function referenceName(ReferenceName|string $referenceName): self
    {
        return self::all()->with(referenceName: $referenceName);
    }

    public function with(
        ReferenceName|string $referenceName = null,
    ): self {
        if (is_string($referenceName)) {
            $referenceName = ReferenceName::fromString($referenceName);
        }
        return new self(
            $referenceName ?? $this->referenceName,
        );
    }
}
