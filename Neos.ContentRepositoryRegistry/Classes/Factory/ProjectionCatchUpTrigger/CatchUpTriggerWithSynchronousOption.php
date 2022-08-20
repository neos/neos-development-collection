<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger;

use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepository\Projection\Projections;
use Neos\ContentRepository\Factory\ContentRepositoryIdentifier;

/**
 */
class CatchUpTriggerWithSynchronousOption implements ProjectionCatchUpTriggerInterface
{
    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    private static bool $synchronousEnabled = false;

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
        private readonly ContentRepositoryIdentifier $contentRepositoryIdentifier,
        private readonly SubprocessProjectionCatchUpTrigger $inner
    )
    {
    }

    public function triggerCatchUp(Projections $projections): void
    {
        if (self::$synchronousEnabled) {
            $contentRepository = $this->contentRepositoryRegistry->get($this->contentRepositoryIdentifier);
            foreach ($projections as $projection) {
                $projectionClassName = get_class($projection);
                $contentRepository->catchUpProjection($projectionClassName);
            }
        } else {
            $this->inner->triggerCatchUp($projections);
        }
    }
}
