<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Service;

use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\AssetUsage\Projection\AssetUsageFinder;
use Neos\Neos\AssetUsage\Projection\AssetUsageRepositoryFactory;

/**
 * @implements ContentRepositoryServiceFactoryInterface<AssetUsageSyncService>
 * @internal
 */
class AssetUsageSyncServiceFactory implements ContentRepositoryServiceFactoryInterface
{
    public function __construct(
        private readonly AssetRepository $assetRepository,
        private readonly AssetUsageRepositoryFactory $assetUsageRepositoryFactory,
    ) {
    }

    public function build(
        ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies,
    ): AssetUsageSyncService {
        return new AssetUsageSyncService(
            $serviceFactoryDependencies->contentRepository,
            $serviceFactoryDependencies->contentRepository->projectionState(AssetUsageFinder::class),
            $this->assetRepository,
            $this->assetUsageRepositoryFactory->build($serviceFactoryDependencies->contentRepositoryId),
        );
    }
}
