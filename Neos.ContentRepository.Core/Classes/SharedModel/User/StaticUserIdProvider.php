<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\User;

/**
 * A user id provider that just statically returns the same user id that it was given upon construction time
 *
 * @api
 */
final class StaticUserIdProvider implements UserIdProviderInterface
{
    public function __construct(
        private readonly UserId $userId,
    ) {
    }

    public function getUserId(): UserId
    {
        return $this->userId;
    }
}
