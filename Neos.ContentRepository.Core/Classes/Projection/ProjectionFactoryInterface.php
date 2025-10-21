<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

use Neos\ContentRepository\Core\Factory\SubscriberFactoryDependencies;

/**
 * @template-covariant T of ProjectionInterface
 * @api
 */
interface ProjectionFactoryInterface
{
    /**
     * @param array<string,mixed> $options
     * @return T
     */
    public function build(
        SubscriberFactoryDependencies $projectionFactoryDependencies,
        array $options,
    ): ProjectionInterface;
}
