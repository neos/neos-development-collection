<?php

declare(strict_types=1);

namespace Neos\Neos\AssetUsage\Projection;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;

/**
 * @internal
 */
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
            sprintf('cr_%s_p_neos_%s', $contentRepositoryId->value, 'asset_usage')
        );
    }
}
