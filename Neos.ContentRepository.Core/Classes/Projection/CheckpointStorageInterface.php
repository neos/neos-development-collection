<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

use Neos\EventStore\Model\Event\SequenceNumber;

/**
 * Contract for a central authority that keeps track of which event has been processed by a single {@see ProjectionInterface} to prevent
 * the same event to be applied multiple times.
 *
 * Implementations of this interface should start an exclusive lock with {@see self::acquireLock()} in order to prevent a
 * separate instance (potentially in a separate process) to return the same {@see SequenceNumber}.
 *
 * An instance of this class is always ever responsible for a single projection.
 * If both, the projection and its checkpoint storage, use the same backend (for example the same database connection)
 * to manage their state, Exactly-Once Semantics can be guaranteed.
 *
 * See {@see CatchUp} for an explanation what this class does in detail.
 * @api
 */
interface CheckpointStorageInterface
{
    /**
     * Initialize this instance
     *
     * Note: Calling this method should be an idempotent operation, i.e. it should be possible to call it multiple times without further side effects
     *
     * Note: This can be used to initially set up this instance (e.g. create required database tables etc.)
     * If an implementation does not need an explicit setup step, the method body can be left empty
     */
    public function setUp(): void;

    /**
     * Obtain an exclusive lock (to prevent multiple instances from being executed simultaneously)
     * and return the highest {@see SequenceNumber} that was processed by this checkpoint storage.
     *
     * Note: Some implementations require to be initialized once ({@see ProjectionInterface::setUp()})
     *
     * @return SequenceNumber The sequence number that was previously set via {@see updateAndReleaseLock()} or SequenceNumber(0) if it was not updated before
     */
    public function acquireLock(): SequenceNumber;

    /**
     * Store the new {@see SequenceNumber} and release the lock
     *
     * @param SequenceNumber $sequenceNumber The sequence number to store – usually after the corresponding event was processed by a listener or when a projection was reset
     */
    public function updateAndReleaseLock(SequenceNumber $sequenceNumber): void;

    /**
     * @return SequenceNumber the last {@see SequenceNumber} that was set via {@see updateAndReleaseLock()} without acquiring a lock
     */
    public function getHighestAppliedSequenceNumber(): SequenceNumber;
}
