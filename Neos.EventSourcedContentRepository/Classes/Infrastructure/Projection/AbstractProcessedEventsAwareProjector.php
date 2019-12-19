<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Infrastructure\Projection;

use Doctrine\DBAL\Connection;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\EventSourcedContentRepository\Service\Infrastructure\Service\DbalClient;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventListener\AppliedEventsStorage\DoctrineAppliedEventsStorage;
use Neos\EventSourcing\EventStore\EventEnvelope;

abstract class AbstractProcessedEventsAwareProjector implements ProcessedEventsAwareProjectorInterface
{

    /**
     * @Flow\Inject
     * @var VariableFrontend
     */
    protected $processedEventsCache;

    /**
     * @var DbalClient
     */
    private $client;

    /**
     * @var bool
     */
    private $assumeProjectorRunsSynchronously = false;

    /**
     * @var string[]
     */
    private $processedEventIdentifiers = [];

    /**
     * @var DoctrineAppliedEventsStorage
     */
    private $doctrineAppliedEventsStorage;

    public function injectDbalClient(DbalClient $client): void
    {
        $this->client = $client;
        $this->doctrineAppliedEventsStorage = new DoctrineAppliedEventsStorage($this->getDatabaseConnection(), get_class($this));
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

    protected function getDatabaseConnection(): Connection
    {
        return $this->client->getConnection();
    }

    /**
     * @param callable $operations
     * @throws \Exception
     * @throws \Throwable
     */
    protected function transactional(callable $operations): void
    {
        $this->getDatabaseConnection()->transactional($operations);
    }

    public function hasProcessed(DomainEvents $events): bool
    {
        if ($this->assumeProjectorRunsSynchronously) {
            // if we run synchronously (e.g. during an import), we *know* that events have been processed already.
            return true;
        }
        foreach ($events as $event) {
            if (!$event instanceof DecoratedEvent || !$event->hasIdentifier()) {
                throw new \RuntimeException(sprintf('The given DomainEvents instance contains an event "%s" that has not identifier', get_class($event)), 1550314769);
            }
            if (!$this->processedEventsCache->has(md5($event->getIdentifier()))) {
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
        $this->doctrineAppliedEventsStorage->releaseHighestAppliedSequenceNumber();
        foreach ($this->processedEventIdentifiers as $eventIdentifier) {
            $this->processedEventsCache->set(md5($eventIdentifier), true);
        }
    }
}
