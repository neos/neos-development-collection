<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\EventStore;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\EventStore\DoctrineAdapter\DoctrineEventStore;
use Neos\EventStore\EventStoreInterface;
use Psr\Clock\ClockInterface;

class DoctrineEventStoreFactory implements EventStoreFactoryInterface
{
    public function __construct(
        private readonly Connection $connection,
    )
    {
    }

    public function build(ContentRepositoryId $contentRepositoryIdentifier, array $contentRepositorySettings, array $eventStorePreset): EventStoreInterface
    {
        $clock = new class implements ClockInterface {
            public function now(): \DateTimeImmutable {
                return new \DateTimeImmutable();
            }
        };
        return new DoctrineEventStore(
            $this->connection,
            self::databaseTableName($contentRepositoryIdentifier),
            $clock
        );
    }

    public static function databaseTableName(ContentRepositoryId $contentRepositoryIdentifier): string
    {
        return sprintf('cr_%s_events', $contentRepositoryIdentifier);
    }
}
