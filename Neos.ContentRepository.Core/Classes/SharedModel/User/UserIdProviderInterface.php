<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\User;

/**
 * @internal except for CR factory implementations
 */
interface UserIdProviderInterface
{
    public function getUserId(): UserId;
}
