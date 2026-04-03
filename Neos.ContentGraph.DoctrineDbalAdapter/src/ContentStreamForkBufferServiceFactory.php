<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;

/**
 * @implements ContentRepositoryServiceFactoryInterface<ContentStreamForkBufferService>
 */
class ContentStreamForkBufferServiceFactory implements ContentRepositoryServiceFactoryInterface
{
    public function __construct(
        private readonly Connection $dbal
    ) {
    }

    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentRepositoryServiceInterface
    {
        return new ContentStreamForkBufferService(
            ContentGraphTableNames::create($serviceFactoryDependencies->contentRepositoryId),
            $this->dbal
        );
    }
}
