<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Export;

/**
 * Common interface for a single step in an import/export process
 */
interface ProcessorInterface
{
    public function run(ProcessingContext $context): void;
}
