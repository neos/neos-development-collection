<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Subscription\Engine;

/**
 * @internal implementation detail of the catchup
 */
final readonly class ProcessedResult
{
    private function __construct(
        public int $numberOfProcessedEvents,
        public Errors|null $errors,
    ) {
    }

    public static function success(int $numberOfProcessedEvents): self
    {
        return new self($numberOfProcessedEvents, null);
    }

    public static function failed(int $numberOfProcessedEvents, Errors $errors): self
    {
        return new self($numberOfProcessedEvents, $errors);
    }

    /** @phpstan-assert-if-true !null $this->errors */
    public function hadErrors(): bool
    {
        return $this->errors !== null;
    }
}
