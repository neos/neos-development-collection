<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\UserIdProvider;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\User\UserIdProviderInterface;

/**
 * @api
 */
interface UserIdProviderFactoryInterface
{
    public function build(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings, array $contentRepositoryPreset): UserIdProviderInterface;
}
