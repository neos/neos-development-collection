<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\UserIdProvider;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\User\UserIdProviderInterface;

interface UserIdProviderFactoryInterface
{
    public function build(ContentRepositoryId $contentRepositoryIdentifier, array $contentRepositorySettings, array $projectionCatchUpTriggerPreset): UserIdProviderInterface;
}
