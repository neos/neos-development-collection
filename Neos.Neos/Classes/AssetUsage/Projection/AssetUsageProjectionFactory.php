<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Projection;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Factory\ProjectionFactoryDependencies;
use Neos\ContentRepository\Core\Projection\ProjectionFactoryInterface;
use Neos\Media\Domain\Repository\AssetRepository;

/**
 * @implements ProjectionFactoryInterface<AssetUsageProjection>
 * @internal
 */
final class AssetUsageProjectionFactory implements ProjectionFactoryInterface
{
    public function __construct(
        private readonly Connection $dbal,
        private readonly AssetUsageRepositoryFactory $assetUsageRepositoryFactory,
        private readonly AssetRepository $assetRepository,
    ) {
    }

    public function build(
        ProjectionFactoryDependencies $projectionFactoryDependencies,
        array $options,
    ): AssetUsageProjection {
        return new AssetUsageProjection(
            $this->assetRepository,
            $projectionFactoryDependencies->contentRepositoryId,
            $this->dbal,
            $this->assetUsageRepositoryFactory,
        );
    }
}
