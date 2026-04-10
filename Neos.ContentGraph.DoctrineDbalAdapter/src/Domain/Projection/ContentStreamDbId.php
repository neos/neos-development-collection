<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

/**
 * @internal
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
