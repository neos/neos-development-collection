<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\Feature\Common\PublishableToWorkspaceInterface;
use Neos\EventStore\Model\EventEnvelope;

interface SimulationContentGraphProjectionInterface
{
    public function getState(): ContentGraphReadModelInterface;

    public function apply(PublishableToWorkspaceInterface $event, EventEnvelope $eventEnvelope): void;

    public function stopSimulation(): void;
}
