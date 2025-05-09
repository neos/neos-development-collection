<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\AssetInterface;
use Neos\Media\Domain\Strategy\AssetUsageStrategyInterface;
use Neos\Neos\AssetUsage\Dto\AssetUsageFilter;
use Neos\Neos\AssetUsage\Dto\AssetUsageReference;
use Neos\Neos\AssetUsage\Dto\AssetUsagesByContentRepository;

/**
 * Implementation of the Neos AssetUsageStrategyInterface in order to protect assets in use
 * to be deleted via the Media Module.
 *
 * @api
 */
#[Flow\Scope('singleton')]
final class AssetUsageStrategy implements AssetUsageStrategyInterface
{
    /**
     * @var array<string, AssetUsagesByContentRepository>
     */
    private array $runtimeCache = [];

    public function __construct(
        private readonly GlobalAssetUsageService $globalAssetUsageService,
        private readonly PersistenceManagerInterface $persistenceManager,
    ) {
    }

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
        $convertedUsages = [];
        foreach ($this->getUsages($asset) as $contentRepositoryId => $usages) {
            foreach ($usages as $usage) {
                $convertedUsages[] = new AssetUsageReference(
                    $asset,
                    ContentRepositoryId::fromString($contentRepositoryId),
                    $usage->workspaceName,
                    $usage->originDimensionSpacePoint,
                    $usage->nodeAggregateId
                );
            }
        }
        return $convertedUsages;
    }

    private function getUsages(AssetInterface $asset): AssetUsagesByContentRepository
    {
        $assetId = $this->persistenceManager->getIdentifierByObject($asset);
        if (!is_string($assetId)) {
            throw new \InvalidArgumentException('The specified asset has no valid id', 1649236892);
        }
        if (!isset($this->runtimeCache[$assetId])) {
            $filter = AssetUsageFilter::create()
                ->withAsset($assetId)
                ->includeVariantsOfAsset()
                ->groupByNode();
            $this->runtimeCache[$assetId] = $this->globalAssetUsageService->findByFilter($filter);
        }
        return $this->runtimeCache[$assetId];
    }
}
