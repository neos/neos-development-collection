<?php

declare(strict_types=1);

namespace Neos\Neos\Security\ContentRepositoryAuthProvider;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\Factory\AuthProvider\AuthProviderFactoryInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\Domain\Service\WorkspaceService;

/**
 * Implementation of the {@see AuthProviderFactoryInterface} in order to provide authentication and authorization for Content Repositories
 *
 * @api
 */
#[Flow\Scope('singleton')]
final readonly class ContentRepositoryAuthProviderFactory implements AuthProviderFactoryInterface
{
    public function __construct(
        private UserService $userService,
        private WorkspaceService $workspaceService,
        private SecurityContext $securityContext,
        private PolicyService $policyService,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): ContentRepositoryAuthProvider
    {
        return new ContentRepositoryAuthProvider($contentRepositoryId, $this->userService, $this->workspaceService, $this->securityContext, $this->policyService);
    }
}
