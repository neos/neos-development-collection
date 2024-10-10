<?php

declare(strict_types=1);

namespace Neos\Neos\UserIdProvider;

use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\User\UserIdProviderInterface;
use Neos\Neos\Domain\Service\UserService;

/**
 * @api
 */
final class UserIdProvider implements UserIdProviderInterface
{
    public function __construct(
        private readonly UserService $userService
    ) {
    }

    public function getUserId(): UserId
    {
        $user = $this->userService->getCurrentUser();
        if ($user === null) {
            return UserId::forSystemUser();
        }
        return UserId::fromString($user->getId()->value);
    }
}
