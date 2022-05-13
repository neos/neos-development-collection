<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Feature\Migration\Transformation;

interface TransformationFactoryInterface
{
    /**
     * @param array<string,mixed> $settings
     */
    public function build(
        array $settings
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface;
}
