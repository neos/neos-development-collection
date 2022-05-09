<?php

namespace Neos\ContentRepository\Feature\Migration\Filter;

interface FilterFactoryInterface
{

    public function build(array $settings): NodeAggregateBasedFilterInterface|NodeBasedFilterInterface;
}
