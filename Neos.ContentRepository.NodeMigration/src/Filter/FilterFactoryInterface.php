<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeMigration\Filter;

use Neos\ContentRepository\Core\ContentRepository;

/**
 * Factory to build a filter
 */
interface FilterFactoryInterface
{
    /**
     * @param array<string,mixed> $settings
     */
    public function build(array $settings, ContentRepository $contentRepository): NodeAggregateBasedFilterInterface|NodeBasedFilterInterface;
}
