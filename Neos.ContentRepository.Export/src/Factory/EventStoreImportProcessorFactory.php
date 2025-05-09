<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Export\Factory;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Export\Processors\EventStoreImportProcessor;

/**
 * @implements ContentRepositoryServiceFactoryInterface<EventStoreImportProcessor>
 */
final readonly class EventStoreImportProcessorFactory implements ContentRepositoryServiceFactoryInterface
{
    public function __construct(
        private WorkspaceName $targetWorkspaceName,
        private bool $keepEventIds,
    ) {
    }

    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): EventStoreImportProcessor
    {
        return new EventStoreImportProcessor(
            $this->targetWorkspaceName,
            $this->keepEventIds,
            $serviceFactoryDependencies->eventStore,
            $serviceFactoryDependencies->eventNormalizer
        );
    }
}
