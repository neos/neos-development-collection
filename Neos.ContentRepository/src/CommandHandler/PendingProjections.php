<?php

declare(strict_types=1);

namespace Neos\ContentRepository\CommandHandler;

use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\Projection\ProjectionInterface;
use Neos\ContentRepository\Projection\Projections;
use Neos\ContentRepository\Projection\ProjectionStateInterface;
use Neos\EventStore\CatchUp\CheckpointStorageInterface;
use Neos\EventStore\Model\Events;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventStore\CommitResult;

/**
 * Implementation detail of {@see ContentRepository::handle()}; needed for implementing {@see CommandResult::block()}.
 *
 * It contains the {@see PendingProjections} (i.e. all affected projections by the command and their target sequence
 * number) for the blocking mechanism.
 *
 * It also contains the {@see CommitResult} that contains the highest sequence number and version of the published
 * events.
 *
 * ## Background: How does blocking work?
 *
 * We need to wait until the projection has consumed the events from our current command. This becomes a bit more
 * complicated in the implementation: For performance reasons, we do NOT send every event to every projection; but only
 * events which can be handled by a given projection (see the {@see ProjectionInterface::canHandle()} method).
 *
 * Let's add an example (time flows from left to right):
 *
 * ```
 * sequenceNumber:         2   3   4   5   6   7
 * projectionA:            x       x
 * projectionB:            x   x   x   x   x
 * projectionC:
 * ```
 *
 * `projectionA` is only interested in the events 2 and 4; projectionB in 2-6; and projectionC is not called at all.
 * This means the {@see CheckpointStorageInterface::getHighestAppliedSequenceNumber()} will return `4` for
 * `projectionA` and `6` for `projectionB` when the projections are all up to date.
 *
 * **Thus, we need to figure out how long to wait (until which sequence number) for each projection individually,
 * and this is what this class does.**
 *
 * ## Algorithm: Calculating the maximum-sequence-number for each projection
 *
 * (implemented in {@see PendingProjections::fromProjectionsAndEventsAndSequenceNumber()})
 *
 * As *input*, we get the highestCommittedSequenceNumber from {@see CommitResult::$highestCommittedSequenceNumber}; so
 * in the example above, this would be `7`. Additionally, we get the just-committed events (for which we want to
 * calculate the above), and all the projections.
 *
 * Additionally, we rely on the fact that the sequence number is monotonically increasing, **without gaps**. We then can
 * calculate the 1st sequence number by doing `highestCommittedSequenceNumber - count(events) + 1`; so in our example
 * above `7-6+1 = 2`.
 *
 * We then iterate over all sequence numbers (2-7 in the example) and ask each {@see ProjectionInterface::canHandle()}
 * whether it was interested in the event. We then remember the highest sequence number which each projection was
 * interested in.
 *
 * NOTE: We could also iterate backwards instead of forward - this would be a bit more efficient because we could break
 *       if we saw a projection for the first time; but maybe even more difficult code-wise.
 *
 * ## Blocking: Putting the parts together
 *
 * - The algorithm below is triggered by {@see ContentRepository::handle()}; and the resulting {@see PendingProjections}
 *   object is passed to {@see CommandResult}.
 * - {@see CommandResult::block()} does busy-waiting; repeatedly calling {@see ProjectionInterface::getSequenceNumber()}
 * - {@see ProjectionInterface::getSequenceNumber()} is typically implemented internally via
 *   {@see CheckpointStorageInterface::getHighestAppliedSequenceNumber()}.#
 *
 * @internal
 */
final class PendingProjections
{
    /**
     * @param Projections<ProjectionInterface<ProjectionStateInterface>> $projections
     * @param array<string, int> $sequenceNumberPerProjection
     */
    public function __construct(
        public readonly Projections $projections,
        private readonly array $sequenceNumberPerProjection,
    ) {
    }

    public static function fromProjectionsAndEventsAndSequenceNumber(
        Projections $allProjections,
        Events $events,
        SequenceNumber $highestCommittedSequenceNumber
    ): self {
        $sequenceNumberInteger = $highestCommittedSequenceNumber->value - $events->count() + 1;
        $pendingProjections = Projections::create();
        $sequenceNumberPerProjection = [];
        foreach ($events as $event) {
            foreach ($allProjections as $projection) {
                if ($projection->canHandle($event)) {
                    $sequenceNumberPerProjection[$projection::class] = $sequenceNumberInteger;
                    if (!$pendingProjections->has($projection::class)) {
                        $pendingProjections = $pendingProjections->with($projection);
                    }
                }
            }
            $sequenceNumberInteger++;
        }
        return new self($pendingProjections, $sequenceNumberPerProjection);
    }

    /**
     * @param ProjectionInterface<ProjectionStateInterface> $projection
     * @return SequenceNumber
     */
    public function getExpectedSequenceNumber(ProjectionInterface $projection): SequenceNumber
    {
        if (!array_key_exists($projection::class, $this->sequenceNumberPerProjection)) {
            throw new \InvalidArgumentException(
                sprintf('Projection of class "%s" is not pending', $projection::class),
                1652252976
            );
        }
        return SequenceNumber::fromInteger($this->sequenceNumberPerProjection[$projection::class]);
    }
}
