<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Service;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Neos\AssetUsage\AssetUsageFinder;
use Neos\Neos\AssetUsage\Dto\AssetUsageFilter;
use Neos\Neos\AssetUsage\Dto\AssetUsages;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\AssetUsage\Projection\AssetUsageRepositoryFactory;
use Neos\Neos\AssetUsage\Projection\AssetUsageRepository;

/**
 * @internal
 */
class GlobalAssetUsageService implements ContentRepositoryServiceInterface
{
    /**
     * @var array<string, mixed>
     */
    #[Flow\InjectConfiguration(path: "AssetUsage")]
    protected array $flowSettings;

    /**
     * @var array<string, ContentRepository>
     */
    private ?array $repositories = null;

    /**
     * @var array<string, AssetUsageRepository>
     */
    private ?array $assetUsageRepositories = null;

    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        private readonly AssetUsageRepositoryFactory $assetUsageRepositoryFactory,
    ) {
    }

    public function findAssetUsageByAssetId(string $assetId): AssetUsages
    {
        $filter = AssetUsageFilter::create()
            ->withAsset($assetId)
            ->includeVariantsOfAsset()
            ->groupByNode();

        $assetUsages = [];
        foreach ($this->getContentRepositories() as $contentRepository) {
            $assetUsages[] = $contentRepository->projectionState(AssetUsageFinder::class)->findByFilter($filter);
        }
        return AssetUsages::fromArrayOfAssetUsages(array_merge(...$assetUsages));
    }

    public function removeAssetUsageByAssetId(string $assetId): void
    {
        foreach ($this->getContentRepositories() as $contentRepositoryId => $contentRepository) {
            $this->getAssetUsageRepository(ContentRepositoryId::fromString($contentRepositoryId))->removeAsset($assetId);
        }
    }

    /**
     * @return array<ContentRepository>
     */
    private function getContentRepositories(): array
    {
        if ($this->repositories === null) {
            $this->repositories = [];

            $repositoryIds = $this->flowSettings['contentRepositories'];
            foreach ($repositoryIds as $contentRepositoryId => $enabled) {
                if ($enabled !== true) {
                    continue;
                }
                $contentRepositoryId = ContentRepositoryId::fromString($contentRepositoryId);

                $this->repositories[$contentRepositoryId->value] = $this->contentRepositoryRegistry->get(
                    $contentRepositoryId
                );
            }
        }

        return $this->repositories;
    }

    private function getAssetUsageRepository(ContentRepositoryId $contentRepositoryId): AssetUsageRepository
    {
        if (!array_key_exists($contentRepositoryId->value, $this->assetUsageRepositories)) {
            $this->assetUsageRepositories[$contentRepositoryId->value] = $this->assetUsageRepositoryFactory->build($contentRepositoryId);
        }

        return $this->assetUsageRepositories[$contentRepositoryId->value];
    }
}
