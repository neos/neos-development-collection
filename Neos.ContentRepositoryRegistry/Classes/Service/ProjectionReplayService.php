<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Service;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\Projection\CatchUpOptions;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\Projections;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngine;
use Neos\ContentRepository\Core\Subscription\Engine\SubscriptionEngineCriteria;
use Neos\ContentRepository\Core\Subscription\SubscriptionId;
use Neos\EventStore\EventStoreInterface;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventStream\VirtualStreamName;

/**
 * Content Repository service to perform Projection replays
 *
 * @internal this is currently only used by the {@see CrCommandController}
 */
final class ProjectionReplayService implements ContentRepositoryServiceInterface
{
    public function __construct(
        private readonly SubscriptionEngine $subscriptionEngine,
    ) {
    }

    public function replayProjection(string $projectionAliasOrClassName, CatchUpOptions $options): void
    {
        // TODO $this->subscriptionEngine->reset()
        // TODO $this->subscriptionEngine->run()
    }

    public function replayAllProjections(CatchUpOptions $options, ?\Closure $progressCallback = null): void
    {
        // TODO $this->subscriptionEngine->reset()
        // TODO $this->subscriptionEngine->run()
    }

    public function resetAllProjections(): void
    {
        // TODO $this->subscriptionEngine->reset()
    }
}
