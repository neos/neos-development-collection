<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Export\Processors;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Export\ProcessingContext;
use Neos\ContentRepository\Export\ProcessorInterface;
use Neos\ContentRepository\Export\Severity;

/**
 * Processor that sets up a content repository instance (creating required database tables, ...)
 */
final class ContentRepositorySetupProcessor implements ProcessorInterface, ContentRepositoryServiceInterface
{
    public function __construct(
        private readonly ContentRepository $contentRepository,
    ) {
    }

    public function run(ProcessingContext $context): void
    {
        $context->dispatch(Severity::NOTICE, "Setting up content repository \"{$this->contentRepository->id->value}\"");
        $this->contentRepository->setUp();
    }
}
