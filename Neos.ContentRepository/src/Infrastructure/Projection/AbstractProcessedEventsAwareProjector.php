<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Infrastructure\Projection;

use Neos\Cache\Frontend\VariableFrontend;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventListener\AppliedEventsStorage\DoctrineAppliedEventsStorage;
use Neos\EventSourcing\EventStore\EventEnvelope;

/**
 * Abstract base class for projections, for implementing the blocking mechanism
 * (see {@see CommandResult} for explanation)
 * @internal
 */
abstract class AbstractProcessedEventsAwareProjector implements ProcessedEventsAwareProjectorInterface
{
    private bool $assumeProjectorRunsSynchronously = false;

    /**
     * @var array<int,string>
     */
    private array $processedEventIdentifiers = [];

    private DoctrineAppliedEventsStorage $doctrineAppliedEventsStorage;

    private VariableFrontend $processedEventsCache;

    public function __construct(DbalClientInterface $client, VariableFrontend $processedEventsCache)
    {
        $this->doctrineAppliedEventsStorage = new DoctrineAppliedEventsStorage(
            $client->getConnection(),
            get_class($this)
        );
        $this->processedEventsCache = $processedEventsCache;
    }

    public function assumeProjectorRunsSynchronously(): void
    {
        $this->assumeProjectorRunsSynchronously = true;
    }

    public function reset(): void
    {
        $this->processedEventIdentifiers = [];
        $this->processedEventsCache->flush();
    }

    public function hasProcessed(DomainEvents $events): bool
    {
        if ($this->assumeProjectorRunsSynchronously) {
            // if we run synchronously (e.g. during an import), we *know* that events have been processed already.
            return true;
        }
        foreach ($events as $event) {
            if (!$event instanceof DecoratedEvent || !$event->hasIdentifier()) {
                throw new \RuntimeException(sprintf(
                    'The given DomainEvents instance contains an event "%s" that has not identifier',
                    get_class($event)
                ), 1550314769);
            }
            if (!is_null($event->getIdentifier()) && !$this->processedEventsCache->has(md5($event->getIdentifier()))) {
                return false;
            }
        }
        return true;
    }

    public function afterInvoke(EventEnvelope $eventEnvelope): void
    {
        if ($this->assumeProjectorRunsSynchronously) {
            // if we run synchronously (e.g. during an import), we don't need to store processed events
            return;
        }

        // At this point, we simply *record* the processed event identifiers:
        // Background: The changed (projection) database tables are not yet visible in the database, because
        // the transaction opened by reserveHighestAppliedEventSequenceNumber() is not yet closed (this is done
        // inside releaseHighestAppliedSequenceNumber).
        //
        // To prevent a race condition where the CommandResult::blockUntilProjectionsAreUpToDate() returns before
        // the database transaction updating the projection tables is committed, we simply record the processed
        // event identifiers here; and then, inside releaseHighestAppliedSequenceNumber(), we record that
        // the events have been successfully applied
        // (and are VISIBLE in the database because the transaction has committed).
        $this->processedEventIdentifiers[] = $eventEnvelope->getRawEvent()->getIdentifier();
    }

    public function reserveHighestAppliedEventSequenceNumber(): int
    {
        return $this->doctrineAppliedEventsStorage->reserveHighestAppliedEventSequenceNumber();
    }

    public function saveHighestAppliedSequenceNumber(int $sequenceNumber): void
    {
        $this->doctrineAppliedEventsStorage->saveHighestAppliedSequenceNumber($sequenceNumber);
    }

    public function releaseHighestAppliedSequenceNumber(): void
    {
        // the next line commits the database transaction.
        $this->doctrineAppliedEventsStorage->releaseHighestAppliedSequenceNumber();

        // Here, we know that the projection has been updated *and* the database transaction
        // has been committed. So we can confirm this to CommandResult::blockUntilProjectionsAreUpToDate().
        foreach ($this->processedEventIdentifiers as $eventIdentifier) {
            $this->processedEventsCache->set(md5($eventIdentifier), true);
        }
    }
}
