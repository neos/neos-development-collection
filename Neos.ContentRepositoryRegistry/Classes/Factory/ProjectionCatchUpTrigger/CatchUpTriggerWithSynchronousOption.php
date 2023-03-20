<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger;

use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Core\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepository\Core\Projection\Projections;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;

/**
 * Pragmatic performance booster for some "batch" operations needed by the Neos UI.
 *
 * By calling {@see self::synchronously(\Closure)} in your code, all projection updates
 * run inside the synchronously closure will be executed **by YOUR process** (instead
 * of a separate process). This greatly speeds up performance for batch operations
 * like rebase.
 *
 * This will only work if you did not open a database transaction beforehand.
 *
 * We will hopefully get rid of this class at some point; by introducing a NodeAggregate
 * which will take care of constraint enforcement then.
 *
 * @internal
 */
class CatchUpTriggerWithSynchronousOption implements ProjectionCatchUpTriggerInterface
{
    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    private static bool $synchronousEnabled = false;

    /**
     * INTERNAL
     */
    public static function enableSynchonityForSpeedingUpTesting(): void
    {
        self::$synchronousEnabled = true;
    }

    public static function synchronously(\Closure $fn): void
    {
        $previousValue = self::$synchronousEnabled;
        self::$synchronousEnabled = true;
        try {
            $fn();
        } finally {
            self::$synchronousEnabled = $previousValue;
        }
    }

    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId,
        private readonly SubprocessProjectionCatchUpTrigger $inner
    )
    {
    }

    public function triggerCatchUp(Projections $projections): void
    {
        if (self::$synchronousEnabled) {
            $contentRepository = $this->contentRepositoryRegistry->get($this->contentRepositoryId);
            foreach ($projections as $projection) {
                $projectionClassName = get_class($projection);
                $contentRepository->catchUpProjection($projectionClassName);
            }
        } else {
            $this->inner->triggerCatchUp($projections);
        }
    }
}
