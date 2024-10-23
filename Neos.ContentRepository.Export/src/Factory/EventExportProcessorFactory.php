<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Export\Factory;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Export\Processors\EventExportProcessor;

/**
 * @implements ContentRepositoryServiceFactoryInterface<EventExportProcessor>
 */
final readonly class EventExportProcessorFactory implements ContentRepositoryServiceFactoryInterface
{
    public function __construct(
        private ContentStreamId $contentStreamId,
    ) {
    }

    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): EventExportProcessor
    {
        return new EventExportProcessor(
            $this->contentStreamId,
            $serviceFactoryDependencies->eventStore,
        );
    }
}
