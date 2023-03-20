<?php
declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Projection;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Factory\ProjectionFactoryDependencies;
use Neos\ContentRepository\Core\Projection\CatchUpHookFactoryInterface;
use Neos\ContentRepository\Core\Projection\Projections;
use Neos\ContentRepository\Core\Projection\ProjectionFactoryInterface;

final class AssetUsageProjectionFactory implements ProjectionFactoryInterface
{
    public function __construct(
        private readonly Connection $dbal,
        private readonly AssetUsageRepositoryFactory $assetUsageRepositoyFactory,
    ) {
    }

    public function build(
        ProjectionFactoryDependencies $projectionFactoryDependencies,
        array $options,
        CatchUpHookFactoryInterface $catchUpHookFactory,
        Projections $projectionsSoFar,
    ): AssetUsageProjection {
        return new AssetUsageProjection(
            $projectionFactoryDependencies->eventNormalizer,
            $projectionFactoryDependencies->contentRepositoryId,
            $this->dbal,
            $this->assetUsageRepositoyFactory,
        );
    }
}
