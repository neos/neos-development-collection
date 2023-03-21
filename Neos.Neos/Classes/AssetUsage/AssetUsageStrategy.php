<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage;

use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Model\Dto\UsageReference;
use Neos\Media\Domain\Strategy\AssetUsageStrategyInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\AssetUsage\Dto\AssetUsage;
use Neos\Neos\AssetUsage\Service\GlobalAssetUsageService;
use Neos\Neos\AssetUsage\Dto\AssetUsages;
use Neos\Neos\AssetUsage\Dto\AssetUsageReference;

/**
 * Implementation of the Neos AssetUsageStrategyInterface in order to protect assets in use
 * to be deleted via the Media Module.
 *
 * @Flow\Scope("singleton")
 * @api
 */
final class AssetUsageStrategy implements AssetUsageStrategyInterface
{
    /**
     * @var array<string, AssetUsages>
     */
    private array $runtimeCache = [];

    #[Flow\InjectConfiguration(path: "AssetUsage.enabled")]
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
            $usage->contentStreamId,
            $usage->originDimensionSpacePoint,
            $usage->nodeAggregateId
        ));
        return iterator_to_array($convertedUsages, false);
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
