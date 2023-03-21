<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Service;

use Neos\Neos\AssetUsage\AssetUsageFinder;
use Neos\Neos\AssetUsage\Dto\AssetUsageFilter;
use Neos\Neos\AssetUsage\Dto\AssetUsage;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\Neos\AssetUsage\Dto\AssetUsages;
use Neos\Neos\AssetUsage\Projection\AssetUsageRepository;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\ContentRepository\Core\DimensionSpace\ContentDimensionZookeeper;

/**
 * @api
 */
class AssetUsageSyncService implements ContentRepositoryServiceInterface
{
    /**
     * @var array<string, DimensionSpacePoint>|null
     */
    private ?array $dimensionSpacePointsByHash = null;

    /**
     * @var array<string, bool>
     */
    private array $existingAssetsById = [];

    public function __construct(
        private readonly AssetUsageFinder $assetUsageFinder,
        private readonly ContentGraphInterface $contentGraph,
        private readonly ContentDimensionZookeeper $contentDimensionZookeeper,
        private readonly AssetRepository $assetRepository,
        private readonly AssetUsageRepository $assetUsageRepository,
    ) {
    }

    public function findAllUsages(): AssetUsages
    {
        return $this->assetUsageFinder->findByFilter(AssetUsageFilter::create());
    }

    public function removeAssetUsage(AssetUsage $assetUsage): void
    {
        $this->assetUsageRepository->remove($assetUsage);
    }

    public function isAssetUsageStillValid(AssetUsage $usage): bool
    {
        if (!isset($this->existingAssetsById[$usage->assetId])) {
            /** @var AssetInterface|null $asset */
            $asset = $this->assetRepository->findByIdentifier($usage->assetId);
            $this->existingAssetsById[$usage->assetId] = $asset !== null;
        }
        if ($this->existingAssetsById[$usage->assetId] === false) {
            return false;
        }
        $dimensionSpacePoint = $this->getDimensionSpacePointByHash($usage->originDimensionSpacePoint);
        if ($dimensionSpacePoint === null) {
            return false;
        }
        $subGraph = $this->contentGraph->getSubgraph(
            $usage->contentStreamId,
            $dimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );
        $node = $subGraph->findNodeById($usage->nodeAggregateId);
        return $node !== null;
    }

    private function getDimensionSpacePointByHash(string $dimensionSpacePointHash): ?DimensionSpacePoint
    {
        if ($this->dimensionSpacePointsByHash === null) {
            foreach ($this->contentDimensionZookeeper->getAllowedDimensionSubspace() as $dimensionSpacePoint) {
                $this->dimensionSpacePointsByHash[$dimensionSpacePoint->hash] = $dimensionSpacePoint;
            }
        }
        return $this->dimensionSpacePointsByHash[$dimensionSpacePointHash] ?? null;
    }
}
