<?php

declare(strict_types=1);

namespace Neos\Media\Domain\Model;

final readonly class AssetId
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
