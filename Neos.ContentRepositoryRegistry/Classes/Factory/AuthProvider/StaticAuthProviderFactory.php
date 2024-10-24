<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\AuthProvider;

use Neos\ContentRepository\Core\Feature\Security\AuthProviderInterface;
use Neos\ContentRepository\Core\Feature\Security\Dto\UserId;
use Neos\ContentRepository\Core\Feature\Security\StaticAuthProvider;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;

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
