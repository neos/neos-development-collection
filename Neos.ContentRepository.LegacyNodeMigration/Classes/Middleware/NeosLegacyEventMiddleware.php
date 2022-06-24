<?php
declare(strict_types=1);

namespace Neos\ContentRepository\LegacyNodeMigration\Middleware;

use Doctrine\DBAL\Connection;
use League\Flysystem\FilesystemException;
use Neos\ContentRepository\LegacyNodeMigration\NodeDataToEventsMigration;
use Neos\ESCR\Export\Middleware\Context;
use Neos\ESCR\Export\Middleware\Event\ValueObject\Attributes;
use Neos\ESCR\Export\Middleware\Event\ValueObject\ExportedEvent;
use Neos\ESCR\Export\Middleware\MiddlewareInterface;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\EventStore\EventNormalizer;
use Neos\EventSourcing\EventStore\RawEvent;
use Neos\EventSourcing\EventStore\StreamName;
use Ramsey\Uuid\Uuid;

final class NeosLegacyEventMiddleware implements MiddlewareInterface
{

    public function __construct(
        private readonly Connection $connection,
        private readonly EventNormalizer $eventNormalizer,
        private readonly NodeDataToEventsMigration $nodeDataToEventsMigration
    ) {}

    public function getLabel(): string
    {
        return 'Neos Legacy Content Repository events';
    }

    public function processImport(Context $context): void
    {
        throw new \RuntimeException('Importing to lecacy system is not yet supported', 1652947289);
    }

    public function processExport(Context $context): void
    {
        $query = $this->connection->executeQuery('
            SELECT
                *
            FROM
                neos_contentrepository_domain_model_nodedata
            WHERE
                workspace = \'live\'
                AND (movedto IS NULL OR removed=0)
                AND path != \'/\'
            ORDER BY
                parentpath, sortingindex
        ');

        $eventFileResource = fopen('php://temp/maxmemory:5242880', 'rb+');
        if ($eventFileResource === false) {
            throw new \RuntimeException('Failed to create temporary event file resource', 1652876509);
        }

        $attributes = Attributes::create()->withMetadata();
        $now = new \DateTimeImmutable();
        $streamName = StreamName::fromString('content-stream');
        $streamVersion = 0;
        $sequenceNumber = 1;
        foreach ($this->nodeDataToEventsMigration->run($query->iterateAssociative()) as $domainEvent) {
            $eventIdentifier = null;
            $metadata = [];
            if ($domainEvent instanceof DecoratedEvent) {
                $eventIdentifier = $domainEvent->hasIdentifier() ? $domainEvent->getIdentifier() : null;
                $metadata = $domainEvent->getMetadata();
                $domainEvent = $domainEvent->getWrappedEvent();
            }
            $rawEvent = new RawEvent(
                $sequenceNumber ++,
                $this->eventNormalizer->getEventType($domainEvent),
                $this->eventNormalizer->normalize($domainEvent),
                $metadata,
                $streamName,
                $streamVersion,
                $eventIdentifier ?? Uuid::uuid4()->toString(),
                $now
            );
            $streamVersion ++;
            fwrite($eventFileResource, ExportedEvent::fromRawEvent($rawEvent, $attributes)->toJson() . chr(10));
        }
        try {
            $context->files->writeStream('events.jsonl', $eventFileResource);
        } catch (FilesystemException $e) {
            throw new \RuntimeException(sprintf('Failed to write events.jsonl: %s', $e->getMessage()), 1646326885, $e);
        }
        fclose($eventFileResource);
        $numberOfExportedEvents = $sequenceNumber - 1;
        $context->report(sprintf('Imported %d event%s', $numberOfExportedEvents, $numberOfExportedEvents === 1 ? '' : 's'));
    }
}
