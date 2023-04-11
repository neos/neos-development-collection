<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger;

use Neos\ContentRepository\Core\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepositoryRegistry\Command\SubprocessProjectionCatchUpCommandController;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;

/**
 * See {@see SubprocessProjectionCatchUpCommandController} for the inner part
 */
class SubprocessProjectionCatchUpTriggerFactory implements ProjectionCatchUpTriggerFactoryInterface
{
    public function build(ContentRepositoryId $contentRepositoryId, array $contentRepositorySettings, array $projectionCatchUpTriggerPreset): ProjectionCatchUpTriggerInterface
    {
        return new CatchUpTriggerWithSynchronousOption(
            $contentRepositoryId,
            new SubprocessProjectionCatchUpTrigger($contentRepositoryId)
        );
    }
}
