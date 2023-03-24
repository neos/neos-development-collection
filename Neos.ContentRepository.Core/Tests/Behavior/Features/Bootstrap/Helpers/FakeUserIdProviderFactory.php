<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Core\Tests\Behavior\Features\Bootstrap\Helpers;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\User\UserIdProviderInterface;
use Neos\ContentRepositoryRegistry\Factory\UserIdProvider\UserIdProviderFactoryInterface;

final class FakeUserIdProviderFactory implements UserIdProviderFactoryInterface
{

    public function build(ContentRepositoryId $contentRepositoryIdentifier, array $contentRepositorySettings, array $projectionCatchUpTriggerPreset): UserIdProviderInterface
    {
        return new FakeUserIdProvider();
    }
}
