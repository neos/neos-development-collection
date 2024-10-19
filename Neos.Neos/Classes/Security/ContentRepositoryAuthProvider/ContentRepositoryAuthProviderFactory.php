<?php

declare(strict_types=1);

namespace Neos\Neos\Security\ContentRepositoryAuthProvider;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\Factory\AuthProvider\AuthProviderFactoryInterface;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\Security\Authorization\ContentRepositoryAuthorizationService;

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
        private ContentRepositoryAuthorizationService $contentRepositoryAuthorizationService,
        private SecurityContext $securityContext,
    ) {
    }

    /**
     * @param array<string, mixed> $options
     */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): ContentRepositoryAuthProvider
    {
        return new ContentRepositoryAuthProvider($contentRepositoryId, $this->userService, $this->contentRepositoryAuthorizationService, $this->securityContext);
    }
}
