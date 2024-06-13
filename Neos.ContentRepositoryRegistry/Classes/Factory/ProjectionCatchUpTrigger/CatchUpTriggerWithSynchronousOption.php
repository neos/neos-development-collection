<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger;

use Neos\ContentRepository\Core\Projection\CatchUpOptions;
use Neos\ContentRepository\Core\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepository\Core\Projection\Projections;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;

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
 * @deprecated remove me https://github.com/neos/neos-development-collection/pull/4988
 * @internal
 */
class CatchUpTriggerWithSynchronousOption implements ProjectionCatchUpTriggerInterface
{
    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * Hack by setting to true to be always sync mode: https://github.com/neos/neos-development-collection/pull/4988
     */
    private static bool $synchronousEnabled = true;

    /**
     * INTERNAL
     */
    public static function enableSynchronicityForSpeedingUpTesting(): void
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
    ) {
    }

    public function triggerCatchUp(Projections $projections): void
    {
        if (self::$synchronousEnabled) {
            $contentRepository = $this->contentRepositoryRegistry->get($this->contentRepositoryId);
            foreach ($projections as $projection) {
                $projectionClassName = get_class($projection);
                $contentRepository->catchUpProjection($projectionClassName, CatchUpOptions::create());
            }
        } else {
            $this->inner->triggerCatchUp($projections);
        }
    }
}
