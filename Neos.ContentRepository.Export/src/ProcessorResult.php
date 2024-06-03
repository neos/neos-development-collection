<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Export;

final readonly class ProcessorResult
{
    private function __construct(
        public Severity $severity,
        public ?string $message = null
    ) {}

    public static function success(?string $message = null): self
    {
        return new self(Severity::NOTICE, $message);
    }

    public static function error(string $message): self
    {
        return new self(Severity::ERROR, $message);
    }
}
