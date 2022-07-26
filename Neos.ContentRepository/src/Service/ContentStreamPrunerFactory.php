<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Service;

use Neos\ContentRepository\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;

/**
 * @implements ContentRepositoryServiceFactoryInterface<ContentStreamPruner>
 */
class ContentStreamPrunerFactory implements ContentRepositoryServiceFactoryInterface
{
    public function __construct(
        private readonly DbalClientInterface $dbalClient
    ) {
    }



    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentStreamPruner
    {
        return new ContentStreamPruner($serviceFactoryDependencies->contentRepository, $this->dbalClient);
    }
}
