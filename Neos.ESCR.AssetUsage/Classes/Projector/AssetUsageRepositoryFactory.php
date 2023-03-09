<?php
declare(strict_types=1);

namespace Neos\ESCR\AssetUsage\Projector;

use Neos\Flow\Annotations as Flow;
use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Factory\ProjectionFactoryDependencies;
use Neos\ContentRepository\Core\Projection\CatchUpHookFactoryInterface;
use Neos\ContentRepository\Core\Projection\Projections;
use Neos\ContentRepository\Core\Projection\ProjectionFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;

final class AssetUsageRepositoryFactory
{
    public function __construct(
        private readonly Connection $dbal,
    ) {
    }

    public function build(ContentRepositoryId $contentRepositoryId): AssetUsageRepository
    {
        return new AssetUsageRepository(
            $this->dbal,
            sprintf('cr_%s_p_neos_%s', $contentRepositoryId, 'asset_usage')
        );
    }
}
