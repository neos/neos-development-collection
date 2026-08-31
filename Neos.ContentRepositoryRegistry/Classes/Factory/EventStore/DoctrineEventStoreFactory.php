<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\EventStore;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\EventStore\DoctrineAdapter\DoctrineEventStore;
use Neos\EventStore\EventStoreInterface;
use Neos\Flow\Annotations as Flow;
use Psr\Clock\ClockInterface;

class DoctrineEventStoreFactory implements EventStoreFactoryInterface
{
    public function __construct(
        private readonly Connection $connection,
        #[Flow\InjectConfiguration('persistence.backendOptions.dbname', 'Neos.Flow')]
        protected readonly string $databaseName,
    ) {
    }

    /** @param array<string, mixed> $options */
    public function build(ContentRepositoryId $contentRepositoryId, array $options, ClockInterface $clock): EventStoreInterface
    {
        return new DoctrineEventStore(
            $this->connection,
            self::databaseTableName($contentRepositoryId),
            $clock,
            self::advisoryLockSeed($contentRepositoryId),
        );
    }

    public static function databaseTableName(ContentRepositoryId $contentRepositoryId): string
    {
        return sprintf('cr_%s_events', $contentRepositoryId->value);
    }

    /**
     * Database lock names are global. Prevent collision with other event stores.
     * @see https://github.com/neos/eventstore-doctrineadapter/pull/40#issuecomment-5453810048
     */
    private function advisoryLockSeed(ContentRepositoryId $contentRepositoryId): string
    {
        return sprintf('%s.%s', $this->databaseName, $contentRepositoryId->value);
    }
}
