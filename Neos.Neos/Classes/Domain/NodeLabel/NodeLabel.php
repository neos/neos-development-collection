<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\NodeLabel;

final readonly class NodeLabel
{
    private function __construct(
        public string $value
    ) {
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }
}
