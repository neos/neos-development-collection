<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Export;

final class ProcessorResult
{

    private function __construct(
        public readonly Severity $severity,
        public readonly ?string $message = null
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
