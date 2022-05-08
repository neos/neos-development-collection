<?php

namespace Neos\ContentRepository\Feature\Migration\Transformation;

use Neos\ContentRepository\Feature\Migration\Transformation\GlobalTransformationInterface;
use Neos\ContentRepository\Feature\Migration\Transformation\NodeAggregateBasedTransformationInterface;
use Neos\ContentRepository\Feature\Migration\Transformation\NodeBasedTransformationInterface;

interface TransformationFactoryInterface
{

    public function build(array $settings): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface;
}
