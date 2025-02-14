<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\TestSuite\Behavior;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;

class ContentRepositoryInternalsAccessor implements ContentRepositoryServiceFactoryInterface
{
    public ContentRepositoryServiceFactoryDependencies $spiedInternals;
    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentRepositoryServiceInterface
    {
        $this->spiedInternals = $serviceFactoryDependencies;
        return new class implements ContentRepositoryServiceInterface
        {
        };
    }
};
