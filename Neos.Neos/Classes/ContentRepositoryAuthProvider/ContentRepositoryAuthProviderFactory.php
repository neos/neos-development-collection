<?php

declare(strict_types=1);

namespace Neos\Neos\ContentRepositoryAuthProvider;

use Neos\ContentRepository\Core\SharedModel\Auth\AuthProviderInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\Factory\AuthProvider\AuthProviderFactoryInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\Domain\Service\WorkspaceService;

/**
 * Implementation of the {@see AuthProviderFactoryInterface} in order to provide authentication and authorization for Content Repositories
 *
 * @api
 */
#[Flow\Scope('singleton')]
final class ContentRepositoryAuthProviderFactory implements AuthProviderFactoryInterface
{
    public function __construct(
        private readonly UserService $userService,
        private readonly WorkspaceService $workspaceService,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): ContentRepositoryAuthProvider
    {
        return new ContentRepositoryAuthProvider($contentRepositoryId, $this->userService, $this->workspaceService);
    }
}
