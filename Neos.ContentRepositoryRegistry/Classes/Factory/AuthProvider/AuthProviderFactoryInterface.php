<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\AuthProvider;

use Neos\ContentRepository\Core\Feature\Security\AuthProviderInterface;
use Neos\ContentRepository\Core\Projection\ContentGraph\ContentGraphReadModelInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\Factory\AuthProviderFactoryInterface as CoreAuthProviderFactoryInterface;

/**
 * @deprecated will be removed with Neos 10.
 * This factory was unfortunately misplaced into the Neos.ContentRepositoryRegistry package while it must instead reside in Neos.ContentRepository.Core
 * Please use {@see \Neos\ContentRepository\Core\Factory\AuthProviderFactoryInterface} instead!
 */
interface AuthProviderFactoryInterface extends CoreAuthProviderFactoryInterface
{
    public function build(ContentRepositoryId $contentRepositoryId, ContentGraphReadModelInterface $contentGraphReadModel): AuthProviderInterface;
}
