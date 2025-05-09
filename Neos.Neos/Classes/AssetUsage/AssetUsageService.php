<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\AssetUsage\Domain\AssetUsageRepository;
use Neos\Neos\AssetUsage\Dto\AssetUsageFilter;
use Neos\Neos\AssetUsage\Dto\AssetUsages;

/**
 * Central authority to look up or remove asset usages in specific a ContentRepository
 */
#[Flow\Scope('singleton')]
class AssetUsageService
{
    public function __construct(
        private readonly AssetUsageRepository $assetUsageRepository,
    ) {
    }

    public function findByFilter(ContentRepositoryId $contentRepositoryId, AssetUsageFilter $filter): AssetUsages
    {
        return $this->assetUsageRepository->findUsages($contentRepositoryId, $filter);
    }
}
