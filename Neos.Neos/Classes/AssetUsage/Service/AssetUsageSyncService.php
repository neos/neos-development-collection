<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Service;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Neos\AssetUsage\Dto\AssetUsage;
use Neos\Neos\AssetUsage\Dto\AssetUsageFilter;
use Neos\Neos\AssetUsage\Dto\AssetUsages;
use Neos\Neos\AssetUsage\Projection\AssetUsageFinder;
use Neos\Neos\AssetUsage\Projection\AssetUsageRepository;

/**
 * @internal
 */
class AssetUsageSyncService implements ContentRepositoryServiceInterface
{
    /**
     * @var array<string, bool>
     */
    private array $existingAssetsById = [];

    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly AssetUsageFinder $assetUsageFinder,
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
        $dimensionSpacePoint = $usage->originDimensionSpacePoint->toDimensionSpacePoint();

        // FIXME: AssetUsage->workspaceName ?
        $workspace = $this->contentRepository->getWorkspaces()->find(
            fn (Workspace $potentialWorkspace) => $potentialWorkspace->currentContentStreamId->equals($usage->contentStreamId)
        );
        if (is_null($workspace)) {
            return false;
        }
        $subGraph = $this->contentRepository->getContentGraph($workspace->workspaceName)->getSubgraph(
            $dimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );
        $node = $subGraph->findNodeById($usage->nodeAggregateId);
        return $node !== null;
    }
}
