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
    ) {
    }

    /** @param array<string, mixed> $options */
    public function build(ContentRepositoryId $contentRepositoryId, array $options, ClockInterface $clock): EventStoreInterface
    {
        return new DoctrineEventStore(
            $this->connection,
            self::databaseTableName($contentRepositoryId),
            $clock
        );
    }

    public static function databaseTableName(ContentRepositoryId $contentRepositoryId): string
    {
        return sprintf('cr_%s_events', $contentRepositoryId->value);
    }
}
