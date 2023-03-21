<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage;

use Neos\Neos\AssetUsage\Projection\AssetUsageRepository;
use Neos\Neos\AssetUsage\Dto\AssetUsageFilter;
use Neos\Neos\AssetUsage\Dto\AssetUsages;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;

/**
 * Central authority to look up asset usages
 * @api
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
