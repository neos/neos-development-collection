<?php

declare(strict_types=1);

namespace Neos\ESCR\AssetUsage;

use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\Dto\UsageReference;
use Neos\Media\Domain\Strategy\AssetUsageStrategyInterface;
use Neos\Flow\Annotations as Flow;
use Neos\ESCR\AssetUsage\Dto\AssetUsage;
use Neos\ESCR\AssetUsage\Service\GlobalAssetUsageService;
use Neos\ESCR\AssetUsage\Dto\AssetUsages;
use Neos\ESCR\AssetUsage\Dto\AssetUsageReference;

/**
 * Implementation of the Neos AssetUsageStrategyInterface in order to protect assets in use
 * to be deleted via the Media Module.
 *
 * @Flow\Scope("singleton")
 */
final class AssetUsageStrategy implements AssetUsageStrategyInterface
{
    /**
     * @var array<string, AssetUsages>
     */
    private array $runtimeCache = [];

    /**
     * @Flow\InjectConfiguration("enabled")
     */
    protected bool $enabled = false;

    public function __construct(
        private readonly GlobalAssetUsageService $globalAssetUsageService,
        private readonly PersistenceManagerInterface $persistenceManager,
    ) {
    }

    public function isInUse(AssetInterface $asset): bool
    {
        if (!$this->enabled) {
            return false;
        }
        return $this->getUsageCount($asset) > 0;
    }

    public function getUsageCount(AssetInterface $asset): int
    {
        if (!$this->enabled) {
            return 0;
        }
        return $this->getUsages($asset)->count();
    }

    public function getUsageReferences(AssetInterface $asset): array
    {
        if (!$this->enabled) {
            return [];
        }
        /** @var \IteratorAggregate<UsageReference> $convertedUsages */
        $convertedUsages = $this->getUsages($asset)->map(fn(AssetUsage $usage) => new AssetUsageReference(
            $asset,
            $usage->contentStreamIdentifier,
            $usage->originDimensionSpacePoint,
            $usage->nodeAggregateIdentifier
        ));
        return iterator_to_array($convertedUsages);
    }

    private function getUsages(AssetInterface $asset): AssetUsages
    {
        $assetId = $this->persistenceManager->getIdentifierByObject($asset);
        if (!is_string($assetId)) {
            throw new \InvalidArgumentException('The specified asset has no valid id', 1649236892);
        }
        if (!isset($this->runtimeCache[$assetId])) {
            $this->runtimeCache[$assetId] = $this->globalAssetUsageService->findAssetUsageByAssetId($assetId);
        }
        return $this->runtimeCache[$assetId];
    }
}
