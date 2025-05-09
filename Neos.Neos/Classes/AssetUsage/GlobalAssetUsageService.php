<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\AssetUsage\Domain\AssetUsageRepository;
use Neos\Neos\AssetUsage\Dto\AssetUsageFilter;
use Neos\Neos\AssetUsage\Dto\AssetUsagesByContentRepository;

/**
 * Central authority to look up or remove asset usages in all configured Content Repositories
 *
 * @api This is used by the {@see AssetUsageStrategy}
 */
#[Flow\Scope('singleton')]
class GlobalAssetUsageService
{
    /**
     * @var array<string, ContentRepository>
     */
    private ?array $contentRepositories = null;

    /**
     * @param array<string, bool> $contentRepositoryIds in the format ['<crId1>' => true, '<crId2>' => false]
     */
    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        private readonly AssetUsageRepository $assetUsageRepository,
        private readonly array $contentRepositoryIds
    ) {
    }

    public function findByFilter(AssetUsageFilter $filter): AssetUsagesByContentRepository
    {
        $assetUsages = [];
        foreach ($this->getContentRepositories() as $contentRepositoryId => $contentRepository) {
            $assetUsages[$contentRepositoryId] = $this->assetUsageRepository->findUsages($contentRepository->id, $filter);
        }
        return new AssetUsagesByContentRepository($assetUsages);
    }

    public function removeAssetUsageByAssetId(string $assetId): void
    {
        foreach ($this->getContentRepositories() as $contentRepositoryId => $contentRepository) {
            $this->assetUsageRepository->removeAsset(ContentRepositoryId::fromString($contentRepositoryId), $assetId);
        }
    }

    /**
     * @return array<ContentRepository>
     */
    private function getContentRepositories(): array
    {
        if ($this->contentRepositories === null) {
            $this->contentRepositories = [];

            foreach ($this->contentRepositoryIds as $contentRepositoryId => $enabled) {
                if ($enabled !== true) {
                    continue;
                }
                $contentRepositoryId = ContentRepositoryId::fromString($contentRepositoryId);

                $this->contentRepositories[$contentRepositoryId->value] = $this->contentRepositoryRegistry->get(
                    $contentRepositoryId
                );
            }
        }

        return $this->contentRepositories;
    }
}
