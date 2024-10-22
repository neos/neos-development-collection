<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\AuthProvider;

use Neos\ContentRepository\Core\Feature\Security\AuthProviderInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;

/**
 * @api
 */
interface AuthProviderFactoryInterface
{
    /** @param array<string, mixed> $options */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): AuthProviderInterface;
}
