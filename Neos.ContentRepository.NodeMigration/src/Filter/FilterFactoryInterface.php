<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeMigration\Filter;

use Neos\ContentRepository\ContentRepository;

interface FilterFactoryInterface
{
    /**
     * @param array<string,mixed> $settings
     */
    public function build(array $settings): NodeAggregateBasedFilterInterface|NodeBasedFilterInterface;
}
