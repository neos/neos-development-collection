<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

/**
 * Forms as a set of {@see ContentStreamLayers} an abstraction for a content stream
 *
 * @internal
 */
final readonly class ContentStreamLayer
{
    private function __construct(
        public int $value
    ) {
        if ($value < 1) {
            throw new \InvalidArgumentException('A ContentStreamLayer must be not be smaller than 1, got %d', $value);
        }
    }

    public function equals(ContentStreamLayer $id): bool
    {
        return $this->value === $id->value;
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }
}
