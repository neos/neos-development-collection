<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Migration\Filter;

interface FilterFactoryInterface
{
    /**
     * @param array<string,mixed> $settings
     */
    public function build(array $settings): NodeAggregateBasedFilterInterface|NodeBasedFilterInterface;
}
