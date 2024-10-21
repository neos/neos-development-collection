<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Export;

use League\Flysystem\Filesystem;

final readonly class ProcessingContext
{
    /**
     * @param \Closure(Severity, string): void $onEvent
     */
    public function __construct(
        public Filesystem $files,
        private \Closure $onEvent,
    ) {
    }

    public function dispatch(Severity $severity, string $message): void
    {
        ($this->onEvent)($severity, $message);
    }
}
