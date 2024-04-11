<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

use Neos\ContentRepository\Core\ContentRepository;

/**
 * Interface for a class that (asynchronously) triggers a catchup of affected projections after a
 * {@see ContentRepository::handle()} call.
 *
 * @api
 */
interface ProjectionCatchUpTriggerInterface
{
    public function triggerCatchUp(): void;
}
