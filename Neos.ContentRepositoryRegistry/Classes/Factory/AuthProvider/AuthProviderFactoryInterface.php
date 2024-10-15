<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\AuthProvider;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Auth\AuthProviderInterface;

/**
 * @api
 */
interface AuthProviderFactoryInterface
{
    /** @param array<string, mixed> $options */
    public function build(ContentRepositoryId $contentRepositoryId, array $options): AuthProviderInterface;
}
