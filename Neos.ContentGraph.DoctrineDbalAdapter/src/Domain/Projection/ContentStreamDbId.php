<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * @internal adapter specific id for content streams.
 * Auto-incrementing int to optimise schema vs unconstrained cr content-stream-ids.
 * Each {@see ContentStreamDbId} points to exactly one {@see ContentStreamId}.
 */
final readonly class ContentStreamDbId
{
    private function __construct(
        public int $value
    ) {
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }
}
