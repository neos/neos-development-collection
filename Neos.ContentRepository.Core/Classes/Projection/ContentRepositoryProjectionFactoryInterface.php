<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

use Neos\ContentRepository\Core\Factory\ProjectionFactoryDependencies;

/**
 * @api
 * @extends ProjectionFactoryInterface<ContentGraphProjectionInterface>
 */
interface ContentRepositoryProjectionFactoryInterface extends ProjectionFactoryInterface
{
    /**
     * @param array<string,mixed> $options
     */
    public function build(
        ProjectionFactoryDependencies $projectionFactoryDependencies,
        array $options,
    ): ContentGraphProjectionInterface;
}
