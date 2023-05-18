<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\PrivilegeProvider;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Privilege\PrivilegeProviderInterface;
use Neos\ContentRepository\Core\SharedModel\User\UserIdProviderInterface;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;

/**
 * @api
 */
interface PrivilegeProviderFactoryInterface
{
    public function build(ContentRepositoryId $contentRepositoryId, array $options, UserIdProviderInterface $userIdProvider, ContentRepositoryRegistry $contentRepositoryRegistry): PrivilegeProviderInterface;
}
