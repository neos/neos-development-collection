<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\CommandHandler;

use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Projection\ProjectionInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\EventStore\Model\Event\SequenceNumber;
use Neos\EventStore\Model\Event\Version;
use Neos\EventStore\Model\EventStore\CommitResult;

/**
 * Result of the {@see ContentRepository::handle()} method to be able to block until the projections were updated.
 *
 * {@see PendingProjections} for a detailed explanation how the blocking works.
 *
 * @api
 */
final readonly class CommandResult
{
    public function __construct(
        private PendingProjections $pendingProjections,
        public CommitResult $commitResult,
    ) {
    }

    /**
     * an empty command result which should not result in projection updates
     * @return self
     */
    public static function empty(): self
    {
        return new self(
            PendingProjections::empty(),
            new CommitResult(
                Version::first(),
                SequenceNumber::none()
            )
        );
    }

    /**
     * Wait until all projections are up to date; i.e. have processed the events.
     *
     * @return void
     * @api
     */
    public function block(): void
    {
        foreach ($this->pendingProjections->projections as $pendingProjection) {
            $expectedSequenceNumber = $this->pendingProjections->getExpectedSequenceNumber($pendingProjection);
            $this->blockProjection($pendingProjection, $expectedSequenceNumber);
        }
    }

    /**
     * @param ProjectionInterface<ProjectionStateInterface> $projection
     */
    private function blockProjection(ProjectionInterface $projection, SequenceNumber $expectedSequenceNumber): void
    {
        $attempts = 0;
        while ($projection->getCheckpointStorage()->getHighestAppliedSequenceNumber()->value < $expectedSequenceNumber->value) {
            usleep(50000); // 50000μs = 50ms
            if (++$attempts > 100) { // 5 seconds
                throw new \RuntimeException(
                    sprintf(
                        'TIMEOUT while waiting for projection "%s" to catch up to sequence number %d ' .
                        '- check the error logs for details.',
                        $projection::class,
                        $expectedSequenceNumber->value
                    ),
                    1550232279
                );
            }
        }
    }
}
