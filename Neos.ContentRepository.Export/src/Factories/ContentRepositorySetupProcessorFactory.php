<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Export\Factories;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Export\Processors\ContentRepositorySetupProcessor;

/**
 * @implements ContentRepositoryServiceFactoryInterface<ContentRepositorySetupProcessor>
 */
final readonly class ContentRepositorySetupProcessorFactory implements ContentRepositoryServiceFactoryInterface
{
    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentRepositorySetupProcessor
    {
        return new ContentRepositorySetupProcessor(
            $serviceFactoryDependencies->contentRepository,
        );
    }
}
