<?php
declare(strict_types=1);

namespace Neos\ESCR\AssetUsage;

use Neos\ESCR\AssetUsage\Projector\AssetUsageRepository;
use Neos\ESCR\AssetUsage\Dto\AssetUsageFilter;
use Neos\ESCR\AssetUsage\Dto\AssetUsages;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;

/**
 * Central authority to look up asset usages
 */
final class AssetUsageFinder implements ProjectionStateInterface
{
    public function __construct(
        readonly private AssetUsageRepository $repository,
    ) {
    }

    public function findByFilter(AssetUsageFilter $filter): AssetUsages
    {
        return $this->repository->findUsages($filter);
    }
}
