<?php
declare(strict_types=1);

namespace Neos\AssetUsage;

use Neos\AssetUsage\Projector\AssetUsageRepository;
use Neos\Flow\Annotations as Flow;
use Neos\AssetUsage\Dto\AssetUsageFilter;
use Neos\AssetUsage\Dto\AssetUsages;

/**
 * Central authority to look up asset usages
 *
 * @Flow\Scope("singleton")
 */
final class AssetUsageFinder
{
    public function __construct(
        readonly private AssetUsageRepository $repository
    ) {}

    public function findByFilter(AssetUsageFilter $filter): AssetUsages
    {
        return $this->repository->findUsages($filter);
    }

}
