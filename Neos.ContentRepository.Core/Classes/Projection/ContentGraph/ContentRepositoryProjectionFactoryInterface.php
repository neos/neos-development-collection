<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\Factory\ProjectionFactoryDependencies;
use Neos\ContentRepository\Core\Projection\ProjectionFactoryInterface;

/**
 * @extends ProjectionFactoryInterface<ContentGraphProjectionInterface>
 * @api for creating a custom content repository graph projection implementation, **not for users of the CR**
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
