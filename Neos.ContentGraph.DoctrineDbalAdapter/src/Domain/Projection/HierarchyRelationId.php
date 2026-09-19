<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

/**
 * @internal
 */
class HierarchyRelationId
{
    private function __construct(
        public ?int $value
    ) {
        if ($value !== null && $value < 0) {
            throw new \InvalidArgumentException('A HierarchyRelationId cannot be negative, got %d', $value);
        }
    }

    public static function createAutoIncremented(): self
    {
        return new self(null);
    }

    public function equals(HierarchyRelationId $id): bool
    {
        return $this->value === $id->value;
    }

    public static function fromInt(int $value): self
    {
        return new self($value);
    }
}
