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
    protected ?array $repositories = null;

    public function __construct(
        protected readonly ContentRepositoryRegistry $contentRepositoryRegistry,
    ) {
    }

    public function findAssetUsageByAssetId(string $assetId): AssetUsages
    {
        $assetUsages = $this->withAllRepositories(
            function (ContentRepository $repository) use ($assetId) {
                $filter = AssetUsageFilter::create()
                    ->withAsset($assetId)
                    ->includeVariantsOfAsset()
                    ->groupByNode();

                return $repository->projectionState(AssetUsageFinder::class)->findByFilter($filter);
            }
        );

        return AssetUsages::fromArrayOfAssetUsages($assetUsages);
    }

    public function removeAssetUsageByAssetId(string $assetId): void
    {
        $this->withAllRepositories(
            fn(AssetUsageRepository $repository) => $repository->removeAsset($assetId)
        );
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

                $this->repositories[(string)$contentRepositoryId] = $this->contentRepositoryRegistry->get(
                    $contentRepositoryId
                );
            }
        }

        return $this->repositories;
    }

    /**
     * @param callable $callback
     * @return array<mixed>
     */
    private function withAllRepositories(callable $callback): array
    {
        return array_map(fn($repository) => $callback($repository), $this->getContentRepositories());
    }
}
