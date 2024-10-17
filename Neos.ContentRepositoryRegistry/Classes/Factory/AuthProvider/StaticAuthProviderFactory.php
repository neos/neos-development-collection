<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\AuthProvider;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Auth\StaticAuthProvider;
use Neos\ContentRepository\Core\SharedModel\Auth\UserId;
use Neos\ContentRepository\Core\SharedModel\Auth\AuthProviderInterface;

/**
 * @api
 */
final class StaticAuthProviderFactory implements AuthProviderFactoryInterface
{
    /** @param array<string, mixed> $options */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): AuthProviderInterface
    {
        return new StaticAuthProvider(UserId::forSystemUser());
    }
}
