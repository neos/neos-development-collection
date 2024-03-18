<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Projection;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\Neos\AssetUsage\Dto\AssetUsageFilter;
use Neos\Neos\AssetUsage\Dto\AssetUsages;
use Neos\Neos\AssetUsage\GlobalAssetUsageService;

/**
 * Finder to look up asset usages for a specific Content Repository
 *
 * To be retrieved via {@see ContentRepository::projectionState()}:
 *
 *   $contentRepository->getProjectionState(AssetUsageProjection::class)
 *
 * To look up usages for all configured Content Repositories, use {@see GlobalAssetUsageService} instead
 *
 * @api
 */
final class AssetUsageFinder implements ProjectionStateInterface
{
    public function __construct(
        private readonly AssetUsageRepository $repository,
    ) {
    }

    public function findByFilter(AssetUsageFilter $filter): AssetUsages
    {
        return $this->repository->findUsages($filter);
    }
}
