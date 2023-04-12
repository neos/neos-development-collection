<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\PrivilegeProvider;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\User\UserIdProviderInterface;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;

/**
 * @internal
 */
final class FakePrivilegeProviderFactory implements PrivilegeProviderFactoryInterface
{
    public function build(ContentRepositoryId $contentRepositoryId, array $options, UserIdProviderInterface $userIdProvider, ContentRepositoryRegistry $contentRepositoryRegistry): FakePrivilegeProvider
    {
        return new FakePrivilegeProvider($userIdProvider, $contentRepositoryRegistry, $contentRepositoryId);
    }
}
