<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\UserIdProvider;

use Neos\ContentRepository\Core\SharedModel\User\UserId;

interface UserIdProviderInterface
{
    public function getUserId(): UserId;
}
