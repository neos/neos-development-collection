<?php
declare(strict_types=1);

namespace Neos\ESCR\AssetUsage;

use Neos\ESCR\AssetUsage\Projector\AssetUsageRepository;
use Neos\Flow\Annotations as Flow;
use Neos\ESCR\AssetUsage\Dto\AssetUsageFilter;
use Neos\ESCR\AssetUsage\Dto\AssetUsages;

/**
 * Central authority to look up asset usages
 *
 * @Flow\Scope("singleton")
 */
final class AssetUsageFinder
{
    public function __construct(
        readonly private AssetUsageRepository $repository
    ) {
    }

    public function findByFilter(AssetUsageFilter $filter): AssetUsages
    {
        return $this->repository->findUsages($filter);
    }
}
