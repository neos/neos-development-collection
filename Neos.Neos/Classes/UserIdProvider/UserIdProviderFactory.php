<?php

declare(strict_types=1);

namespace Neos\Neos\UserIdProvider;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\User\UserIdProviderInterface;
use Neos\ContentRepositoryRegistry\Factory\UserIdProvider\UserIdProviderFactoryInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\UserService;

/**
 * Implementation of the Neos AssetUsageStrategyInterface in order to protect assets in use
 * to be deleted via the Media Module.
 *
 * @api
 */
#[Flow\Scope('singleton')]
final class UserIdProviderFactory implements UserIdProviderFactoryInterface
{
    public function __construct(
        private readonly UserService $userService
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): UserIdProviderInterface
    {
        return new UserIdProvider($this->userService);
    }
}
