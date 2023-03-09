<?php

namespace Neos\ESCR\AssetUsage\Service;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ESCR\AssetUsage\AssetUsageFinder;
use Neos\ESCR\AssetUsage\Dto\AssetUsageFilter;
use Neos\ESCR\AssetUsage\Dto\AssetUsage;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Factory\ContentRepositoryFactory;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\DimensionSpace\ContentDimensionZookeeper;
use Neos\ESCR\AssetUsage\Projector\AssetUsageRepositoryFactory;
use Neos\Neos\Controller\Module\Administration\DimensionControllerInternals;

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