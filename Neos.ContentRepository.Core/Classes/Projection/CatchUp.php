<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Projection;

use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\EventEnvelope;
use Neos\EventStore\Model\EventStream\EventStreamInterface;

/**
 *  This helper class is used to implement the orchestration of projection catch up; together with {@see CheckpointStorageInterface}.
 *
 *  It ensures that a given projection **never runs concurrently** and thus prevents race conditions where the same
 *  projector is accidentally running multiple times in parallel.
 *
 *  If you use the {@see \Neos\ContentRepository\Core\Infrastructure\DbalCheckpointStorage}, and share the same database connection with your projection,
 *  this class **implements Exactly-Once Semantics for your projections**, to ensure each event is seen EXACTLY once in your projection.
 *
 *  ## How does it work?
 *
 *  When you call {@see CatchUp::run()}, a lock is acquired via {@see CheckpointStorageInterface::acquireLock()}
 *  (e.g. from the database), to ensure we run only once (even if in a distributed system). For relational
 *  databases, this also starts a database transaction.
 *
 *  After every batchSize events (typically after every event), we update the sequence number and commit
 *  the transaction (via {@see CheckpointStorageInterface::updateAndReleaseLock()}). Then, we open a new transaction.
 *
 *  In case of errors, the transaction is rolled back. So event listeners with their own state best share the same
 *  database connection.
 *  If a new transaction is started on the same connection within the event listener callback, the transaction will be nested (@see https://www.doctrine-project.org/projects/doctrine-dbal/en/3.7/reference/transactions.html#transaction-nesting)
 *
 * @internal
 */
final class CatchUp
{
    /**
     * @param \Closure(EventEnvelope): void $eventHandler The callback that is invoked for every {@see EventEnvelope} that is processed
     * @param CheckpointStorageInterface $checkpointStorage The checkpoint storage that saves the last processed {@see SequenceNumber}
     * @param int $batchSize Number of events to process before the checkpoint is written (defaults to 1 in order to guarantee exactly-once semantics) – ({@see withBatchSize()})
     * @param \Closure(): void|null $onBeforeBatchCompletedHook Optional callback that is invoked before the sequence number is updated ({@see withOnBeforeBatchCompleted()})
     */
    private function __construct(
        private readonly \Closure $eventHandler,
        private readonly CheckpointStorageInterface $checkpointStorage,
        private readonly int $batchSize,
        private readonly ?\Closure $onBeforeBatchCompletedHook,
    ) {
        if ($this->batchSize < 1) {
            throw new \InvalidArgumentException(sprintf('batch size must be a positive integer, given: %d', $this->batchSize), 1705672467);
        }
    }

    /**
     * @param \Closure(EventEnvelope): void $eventHandler The callback that is invoked for every {@see EventEnvelope} that is processed
     * @param CheckpointStorageInterface $checkpointStorage The checkpoint storage that saves the last processed {@see SequenceNumber}
     */
    public static function create(\Closure $eventHandler, CheckpointStorageInterface $checkpointStorage): self
    {
        return new self($eventHandler, $checkpointStorage, 1, null);
    }

    /**
     * After how many events should the (database) transaction be committed?
     *
     * @param int $batchSize Number of events to process before the checkpoint is written
     */
    public function withBatchSize(int $batchSize): self
    {
        if ($batchSize === $this->batchSize) {
            return $this;
        }
        return new self($this->eventHandler, $this->checkpointStorage, $batchSize, $this->onBeforeBatchCompletedHook);
    }

    /**
     * This hook is called directly before the sequence number is persisted back in CheckpointStorage.
     * Use this to trigger any operation which need to happen BEFORE the sequence number update is made
     * visible to the outside.
     *
     * Overrides all previously registered onBeforeBatchCompleted hooks.
     *
     * @param \Closure(): void $callback the hook being called before the batch is completed
     */
    public function withOnBeforeBatchCompleted(\Closure $callback): self
    {
        return new self($this->eventHandler, $this->checkpointStorage, $this->batchSize, $callback);
    }

    /**
     * Iterate over the $eventStream, invoke the specified event handler closure for every {@see EventEnvelope} and update
     * the last processed sequence number in the {@see CheckpointStorageInterface}
     *
     * @param EventStreamInterface $eventStream The event stream to process
     * @return SequenceNumber The last processed {@see SequenceNumber}
     * @throws \Throwable Exceptions that are thrown during callback handling are re-thrown
     */
    public function run(EventStreamInterface $eventStream): SequenceNumber
    {
        $highestAppliedSequenceNumber = $this->checkpointStorage->acquireLock();
        $iteration = 0;
        try {
            foreach ($eventStream->withMinimumSequenceNumber($highestAppliedSequenceNumber->next()) as $eventEnvelope) {
                if ($eventEnvelope->sequenceNumber->value <= $highestAppliedSequenceNumber->value) {
                    continue;
                }
                try {
                    ($this->eventHandler)($eventEnvelope);
                } catch (\Exception $e) {
                    throw new \RuntimeException(sprintf('Exception while catching up to sequence number %d', $eventEnvelope->sequenceNumber->value), 1710707311, $e);
                }
                $iteration++;
                if ($this->batchSize === 1 || $iteration % $this->batchSize === 0) {
                    if ($this->onBeforeBatchCompletedHook) {
                        ($this->onBeforeBatchCompletedHook)();
                    }
                    $this->checkpointStorage->updateAndReleaseLock($eventEnvelope->sequenceNumber);
                    $highestAppliedSequenceNumber = $this->checkpointStorage->acquireLock();
                } else {
                    $highestAppliedSequenceNumber = $eventEnvelope->sequenceNumber;
                }
            }
        } finally {
            try {
                if ($this->onBeforeBatchCompletedHook) {
                    ($this->onBeforeBatchCompletedHook)();
                }
            } catch (\Throwable $e) {
                $this->checkpointStorage->updateAndReleaseLock($highestAppliedSequenceNumber);
                throw $e;
            }
            $this->checkpointStorage->updateAndReleaseLock($highestAppliedSequenceNumber);
        }
        return $highestAppliedSequenceNumber;
    }
}
