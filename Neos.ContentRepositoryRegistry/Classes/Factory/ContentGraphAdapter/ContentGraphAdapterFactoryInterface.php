<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\ContentGraphAdapter;

use Neos\ContentRepository\Core\SharedModel\ContentGraph\ContentGraphAdapterInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;

/**
 * @api
 */
interface ContentGraphAdapterFactoryInterface
{
    /** @param array<string, mixed> $options */
    public function build(ContentRepositoryId $contentRepositoryIdentifier, array $options): ContentGraphAdapterInterface;
}
