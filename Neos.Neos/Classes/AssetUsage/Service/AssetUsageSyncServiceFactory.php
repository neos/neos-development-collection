<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Service;

use Neos\Neos\AssetUsage\AssetUsageFinder;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\AssetUsage\Projection\AssetUsageRepositoryFactory;

/**
 * @implements ContentRepositoryServiceFactoryInterface<AssetUsageSyncService>
 * @api
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
            $serviceFactoryDependencies->contentRepository->projectionState(AssetUsageFinder::class),
            $serviceFactoryDependencies->contentRepository->getContentGraph(),
            $serviceFactoryDependencies->contentDimensionZookeeper,
            $this->assetRepository,
            $this->assetUsageRepositoryFactory->build($serviceFactoryDependencies->contentRepositoryId),
        );
    }
}
