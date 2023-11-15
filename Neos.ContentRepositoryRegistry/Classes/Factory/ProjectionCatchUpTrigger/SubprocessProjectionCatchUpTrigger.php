<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger;

use Neos\Cache\Frontend\VariableFrontend;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepositoryRegistry\Service\AsynchronousCatchUpRunnerState;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Core\Projection\ProjectionCatchUpTriggerInterface;
use Neos\ContentRepository\Core\Projection\Projections;
use Neos\ContentRepositoryRegistry\Command\SubprocessProjectionCatchUpCommandController;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\Flow\Core\Booting\Scripts;

/**
 * See {@see SubprocessProjectionCatchUpCommandController} for the inner part
 */
class SubprocessProjectionCatchUpTrigger implements ProjectionCatchUpTriggerInterface
{
    /**
     * @Flow\InjectConfiguration(package="Neos.Flow")
     * @var array<string, mixed>
     */
    protected $flowSettings;

    /**
     * @Flow\Inject(name="Neos.ContentRepositoryRegistry:CacheCatchUpStates")
     * @var VariableFrontend
     */
    protected $catchUpStatesCache;

    public function __construct(
        private readonly ContentRepositoryId $contentRepositoryId
    ) {
    }

    public function triggerCatchUp(Projections $projections): void
    {
        // modelled after https://github.com/neos/Neos.EventSourcing/blob/master/Classes/EventPublisher/JobQueueEventPublisher.php#L103
        // and https://github.com/Flowpack/jobqueue-common/blob/master/Classes/Queue/FakeQueue.php
        $queuedProjections = [];
        foreach ($projections as $projection) {
            $runnerState = AsynchronousCatchUpRunnerState::create($this->contentRepositoryId, $projection::class, $this->catchUpStatesCache);
            if (!$runnerState->isRunning()) {
                $this->startCatchUp($projection, $runnerState);
                continue;
            }

            if (!$runnerState->isQueued()) {
                $runnerState->queue();
                $queuedProjections[] = [$projection, $runnerState];
            }
        }

        for ($attempts = 0; $attempts < 50 && !empty($queuedProjections); $attempts++) {
            // Incremental back off with some randomness to get a wide spread between processes.
            usleep(random_int(100, 25000) + ($attempts * $attempts * 10)); // 50000μs = 50ms
            $queuedProjections = $this->recheckQueuedProjections($queuedProjections);
        }
    }

    /**
     * @param array<array{ProjectionInterface<ProjectionStateInterface>, AsynchronousCatchUpRunnerState}> $queuedProjections
     * @return array<array{ProjectionInterface<ProjectionStateInterface>, AsynchronousCatchUpRunnerState}>
     */
    private function recheckQueuedProjections(array $queuedProjections): array
    {
        $nextQueuedProjections = [];
        /**
         * @var ProjectionInterface<ProjectionStateInterface> $projection
         * @var AsynchronousCatchUpRunnerState $runnerState
         */
        foreach ($queuedProjections as [$projection, $runnerState]) {
            // another process has started a catchUp and cleared the queue while we waited, our queue has become irrelevant
            if ($runnerState->isQueued() === false) {
                continue;
            }

            if ($runnerState->isRunning() === false) {
                $this->startCatchUp($projection, $runnerState);
            }

            $nextQueuedProjections[] = [$projection, $runnerState];
        }

        return $nextQueuedProjections;
    }

    /**
     * @param ProjectionInterface<ProjectionStateInterface> $projection
     * @param AsynchronousCatchUpRunnerState $runnerState
     * @return void
     */
    private function startCatchUp(ProjectionInterface $projection, AsynchronousCatchUpRunnerState $runnerState): void
    {
        $runnerState->run();
        // We are about to start a catchUp and can therefore discard any queue that exists right now, apparently someone else is waiting for it.
        $runnerState->dequeue();
        Scripts::executeCommandAsync(
            'neos.contentrepositoryregistry:subprocessprojectioncatchup:catchup',
            $this->flowSettings,
            [
                'contentRepositoryIdentifier' => $this->contentRepositoryId->value,
                'projectionClassName' => $projection::class
            ]
        );
    }
}
