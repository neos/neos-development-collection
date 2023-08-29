<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\Tests\Behavior\Helpers;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\User\UserIdProviderInterface;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\FakeUserIdProvider;
use Neos\ContentRepositoryRegistry\Factory\UserIdProvider\UserIdProviderFactoryInterface;

final class FakeUserIdProviderFactory implements UserIdProviderFactoryInterface
{
    /**
     * @param array<string,mixed> $options
     */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): UserIdProviderInterface
    {
        return new FakeUserIdProvider();
    }
}
