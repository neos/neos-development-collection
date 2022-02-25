<?php
declare(strict_types=1);

namespace Neos\ESCR\AssetUsage;

use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\Dto\UsageReference;
use Neos\Media\Domain\Strategy\AssetUsageStrategyInterface;
use Neos\Flow\Annotations as Flow;
use Neos\ESCR\AssetUsage\Dto\AssetUsage;
use Neos\ESCR\AssetUsage\Dto\AssetUsageFilter;
use Neos\ESCR\AssetUsage\Dto\AssetUsages;

/**
 * Implementation of the Neos AssetUsageStrategyInterface in order to protect assets in use
 * to be deleted via the Media Module.
 *
 * @Flow\Scope("singleton")
 */
final class AssetUsageStrategy implements AssetUsageStrategyInterface
{
    private array $runtimeCache = [];

    public function __construct(
        private readonly AssetUsageFinder $assetUsageFinder,
        private readonly PersistenceManagerInterface $persistenceManager
    ) {}

    public function isInUse(AssetInterface $asset): bool
    {
        return $this->getUsageCount($asset) > 0;
    }

    public function getUsageCount(AssetInterface $asset): int
    {
        return $this->getUsages($asset)->count();
    }

    public function getUsageReferences(AssetInterface $asset): array
    {
        return iterator_to_array($this->getUsages($asset)->map(fn(AssetUsage $usage) => new UsageReference($asset)));
    }

    private function getUsages(AssetInterface $asset): AssetUsages
    {
        $assetId = $this->persistenceManager->getIdentifierByObject($asset);
        if (!isset($this->runtimeCache[$assetId])) {
            $filter = AssetUsageFilter::create()
                ->withAsset($assetId)
                ->groupByNode();
            $this->runtimeCache[$assetId] = $this->assetUsageFinder->findByFilter($filter);
        }
        return $this->runtimeCache[$assetId];
    }
}
