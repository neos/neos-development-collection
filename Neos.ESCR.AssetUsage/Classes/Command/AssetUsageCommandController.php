<?php
declare(strict_types=1);

namespace Neos\ESCR\AssetUsage\Command;

use Neos\ContentRepository\Core\DimensionSpace\ContentDimensionZookeeper;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ESCR\AssetUsage\Dto\AssetUsage;
use Neos\ESCR\AssetUsage\Dto\AssetUsageFilter;
use Neos\ESCR\AssetUsage\Projector\AssetUsageRepository;
use Neos\ContentRepository\Core\Projection\ContentGraph\VisibilityConstraints;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphInterface;
use Neos\Flow\Cli\CommandController;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Repository\AssetRepository;

final class AssetUsageCommandController extends CommandController
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
        private readonly AssetUsageRepository $assetUsageRepository,
        private readonly ContentDimensionZookeeper $contentDimensionZookeeper,
        private readonly ContentGraphInterface $contentGraph,
        private readonly AssetRepository $assetRepository,
    ) {
        parent::__construct();
    }

    /**
     * Remove asset usages that are no longer valid
     *
     * This is the case for usages that refer to
     * * deleted nodes (i.e. nodes that were implicitly removed because an ancestor node was deleted)
     * * invalid dimension space points (e.g. because dimension configuration has been changed)
     * * removed content streams
     *
     * @param bool $quiet if Set, only errors will be outputted
     */
    public function syncCommand(bool $quiet = false): void
    {
        $usages = $this->assetUsageRepository->findUsages(AssetUsageFilter::create());
        if (!$quiet) {
            $this->output->progressStart($usages->count());
        }
        $numberOfRemovedUsages = 0;
        foreach ($usages as $usage) {
            if (!$this->isAssetUsageStillValid($usage)) {
                $this->assetUsageRepository->remove($usage);
                $numberOfRemovedUsages ++;
            }
            if (!$quiet) {
                $this->output->progressAdvance();
            }
        }
        if (!$quiet) {
            $this->output->progressFinish();
            $this->outputLine();
            $this->outputLine('Removed %d asset usage%s', [
                $numberOfRemovedUsages, $numberOfRemovedUsages === 1 ? '' : 's'
            ]);
        }
    }

    private function isAssetUsageStillValid(AssetUsage $usage): bool
    {
        if (!isset($this->existingAssetsById[$usage->assetIdentifier])) {
            /** @var AssetInterface|null $asset */
            $asset = $this->assetRepository->findByIdentifier($usage->assetIdentifier);
            $this->existingAssetsById[$usage->assetIdentifier] = $asset !== null;
        }
        if ($this->existingAssetsById[$usage->assetIdentifier] === false) {
            return false;
        }
        $dimensionSpacePoint = $this->getDimensionSpacePointByHash($usage->originDimensionSpacePoint);
        if ($dimensionSpacePoint === null) {
            return false;
        }
        $subGraph = $this->contentGraph->getSubgraph(
            $usage->contentStreamIdentifier,
            $dimensionSpacePoint,
            VisibilityConstraints::withoutRestrictions()
        );
        $node = $subGraph->findNodeByNodeAggregateIdentifier($usage->nodeAggregateIdentifier);
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
