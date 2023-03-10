<?php

namespace Neos\ESCR\AssetUsage\Service;

use Neos\ESCR\AssetUsage\AssetUsageFinder;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\ESCR\AssetUsage\Projection\AssetUsageRepositoryFactory;

/**
 * @implements ContentRepositoryServiceFactoryInterface<AssetUsageSyncService>
 */
class AssetUsageSyncServiceFactory implements ContentRepositoryServiceFactoryInterface
{

    public function __construct(
        private readonly AssetRepository $assetRepository,
        private readonly AssetUsageRepositoryFactory $assetUsageRepositoryFactory,
    ) {
    }

    public function build(
        ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): AssetUsageSyncService
    {
        return new AssetUsageSyncService(
            $serviceFactoryDependencies->contentRepository->projectionState(AssetUsageFinder::class),
            $serviceFactoryDependencies->contentRepository->getContentGraph(),
            $serviceFactoryDependencies->contentDimensionZookeeper,
            $this->assetRepository,
            $this->assetUsageRepositoryFactory->build($serviceFactoryDependencies->contentRepositoryId),
        );
    }
}