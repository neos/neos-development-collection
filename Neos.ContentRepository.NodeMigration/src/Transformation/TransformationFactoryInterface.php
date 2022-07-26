<?php

declare(strict_types=1);

namespace Neos\ContentRepository\NodeMigration\Transformation;

interface TransformationFactoryInterface
{
    /**
     * @param array<string,mixed> $settings
     */
    public function build(
        array $settings
    ): GlobalTransformationInterface|NodeAggregateBasedTransformationInterface|NodeBasedTransformationInterface;
}
