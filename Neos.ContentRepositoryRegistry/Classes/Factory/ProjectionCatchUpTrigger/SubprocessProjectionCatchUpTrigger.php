<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger;

use Neos\Cache\Frontend\VariableFrontend;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\EventStore\Model\Event\SequenceNumber;
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
        $queuedProjections = array_map($this->startCatchUpWithQueueing(...), iterator_to_array($projections));
        $queuedProjections = array_filter($queuedProjections);

        $attempts = 0;
        while (!empty($queuedProjections)) {
            usleep(random_int(100, 25000) + ($attempts * $attempts * 10)); // 50000μs = 50ms
            if (++$attempts > 50) {
                throw new \RuntimeException('TIMEOUT while waiting for projections to run queued catch up.', 1550232279);
            }

            foreach ($queuedProjections as $key => $projection) {
                if ($this->catchUpStatesCache->has($this->cacheKeyPrefix($projection) . 'QUEUED') === false) {
                    // another process has started a catchUp while we waited, our queue has become irrelevant
                    unset($queuedProjections[$key]);
                }
                $hasStarted = $this->startCatchUp($projection);
                if ($hasStarted) {
                    unset($queuedProjections[$key]);
                    $this->catchUpStatesCache->remove($this->cacheKeyPrefix($projection) . 'QUEUED');
                }
            }
            $queuedProjections = array_values($queuedProjections);
        }
    }

    /**
     * @param ProjectionInterface $projection
     * @return bool has catchUp been started for given projection
     * @throws \Neos\Cache\Exception
     */
    private function startCatchUp(ProjectionInterface $projection): bool
    {
        if ($this->catchUpStatesCache->has($this->cacheKeyPrefix($projection) . 'RUNNING')) {
            return false;
        }

        $this->catchUpStatesCache->set($this->cacheKeyPrefix($projection) . 'RUNNING', 1);
        // We are about to start a catchUp and can therefore discard any QUEUE that exists right now, apparently someone else is waiting for it.
        $this->catchUpStatesCache->remove($this->cacheKeyPrefix($projection) . 'QUEUED');
        Scripts::executeCommandAsync(
            'neos.contentrepositoryregistry:subprocessprojectioncatchup:catchup',
            $this->flowSettings,
            [
                'contentRepositoryIdentifier' => $this->contentRepositoryId->value,
                'projectionClassName' => get_class($projection)
            ]
        );

        return true;
    }

    /**
     * @param ProjectionInterface $projection
     * @return ProjectionInterface|null Returns only projections that have been queued for later retry.
     * @throws \Neos\Cache\Exception
     */
    private function startCatchUpWithQueueing(ProjectionInterface $projection): ?ProjectionInterface
    {
        $catchUpStarted = $this->startCatchUp($projection);
        if ($catchUpStarted) {
            return null;
        }

        if (!$this->catchUpStatesCache->has($this->cacheKeyPrefix($projection) . 'QUEUED')) {
            $this->catchUpStatesCache->set($this->cacheKeyPrefix($projection) . 'QUEUED', 1);
            return $projection;
        }

        return null;
    }

    private function cacheKeyPrefix(ProjectionInterface $projection): string
    {
        $projectionClassName = get_class($projection);
        return md5($this->contentRepositoryId->value . $projectionClassName);
    }
}
