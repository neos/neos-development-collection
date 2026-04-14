<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

/**
 * @internal
 */
final readonly class ContentStreamLayer
{
    private function __construct(
        public int $value
    ) {
        if ($value < 0) {
            throw new \InvalidArgumentException('A ContentStreamLayer cannot be negative, got %d', $value);
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
