<?php

namespace Neos\ESCR\AssetUsage\Service;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ESCR\AssetUsage\AssetUsageFinder;
use Neos\ESCR\AssetUsage\Dto\AssetUsageFilter;
use Neos\ESCR\AssetUsage\Dto\AssetUsages;
use Neos\Flow\Annotations as Flow;
use Neos\ESCR\AssetUsage\Projection\AssetUsageRepository;


class GlobalAssetUsageService implements ContentRepositoryServiceInterface
{
    /**
     * @var array
     */
    #[Flow\InjectConfiguration]
    protected array $flowSettings;

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
                    ->groupByNode();

                return $repository->projectionState(AssetUsageFinder::class)->findByFilter($filter);
            }
        );

        return new AssetUsages(
            function () use ($assetUsages) {
                return array_reduce(
                    $assetUsages,
                    function (\AppendIterator $globalAssetUsages, AssetUsages $assetUsage) {
                        $globalAssetUsages->append($assetUsage->getIterator());
                        return $globalAssetUsages;
                    },
                    new \AppendIterator()
                );
            },
            function () use ($assetUsages) {
                return array_reduce(
                    $assetUsages,
                    fn ($globalCount, AssetUsages $assetUsage) => $globalCount + $assetUsage->count(),
                    0
                );
            }
        );
    }

    public function removeAssetUsageByAssetId(string $assetId): void
    {
        $this->withAllRepositories(
            fn (AssetUsageRepository $repository) => $repository->removeAsset($assetId)
        );
    }

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

    private function withAllRepositories(callable $callback): array
    {
        return array_map(fn ($repository) => $callback($repository), $this->getContentRepositories());
    }
}