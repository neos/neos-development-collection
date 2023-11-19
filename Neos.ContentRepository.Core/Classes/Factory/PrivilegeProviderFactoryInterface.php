<?php

namespace Neos\ContentRepository\Core\Factory;

use Neos\ContentRepository\Core\SharedModel\Privilege\PrivilegeProviderInterface;
use Neos\ContentRepository\Core\SharedModel\User\UserIdProviderInterface;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;

interface PrivilegeProviderFactoryInterface
{
    public function build(ContentRepositoryId $contentRepositoryId, array $options, UserIdProviderInterface $userIdProvider, ContentRepositoryRegistry $contentRepositoryRegistry): PrivilegeProviderInterface;
}
