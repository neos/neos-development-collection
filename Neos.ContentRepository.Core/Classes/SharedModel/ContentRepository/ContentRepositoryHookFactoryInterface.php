<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\SharedModel\ContentRepository;

use Neos\ContentRepository\Core\ContentRepository;

/**
 * @api
 */
interface ContentRepositoryHookFactoryInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function build(ContentRepository $contentRepository, array $options): ContentRepositoryHookInterface;
}
