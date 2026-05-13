<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\Projection\ProjectionInterface;

/**
 * @extends ProjectionInterface<ContentGraphReadModelInterface>
 */
interface SimulationContentGraphProjectionInterface extends ProjectionInterface
{
    public function getState(): ContentGraphReadModelInterface;

    public function stopSimulation(): void;
}
