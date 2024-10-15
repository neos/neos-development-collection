<?php

declare(strict_types=1);

namespace Neos\ContentRepository\BehavioralTests\TestSuite\Behavior;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Auth\AuthProviderInterface;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\FakeAuthProvider;
use Neos\ContentRepositoryRegistry\Factory\AuthProvider\AuthProviderFactoryInterface;

final class FakeAuthProviderFactory implements AuthProviderFactoryInterface
{
    /**
     * @param array<string,mixed> $options
     */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): AuthProviderInterface
    {
        return new FakeAuthProvider();
    }
}
