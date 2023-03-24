<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\UserIdProvider;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\User\StaticUserIdProvider;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\User\UserIdProviderInterface;

/**
 * @api
 */
final class StaticUserIdProviderFactory implements UserIdProviderFactoryInterface
{
    public function build(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings, array $contentRepositoryPreset): UserIdProviderInterface
    {
        return new StaticUserIdProvider(UserId::forSystemUser());
    }
}
