<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Core\SharedModel\User;

final class StaticUserIdProvider implements UserIdProviderInterface
{
    public function __construct(
        private readonly UserId $userId,
    ) {}

    public function getUserId(): UserId
    {
        return $this->userId;
    }
}
