<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\EventStore;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\EventStore\DoctrineAdapter\DoctrineEventStore;
use Neos\EventStore\EventStoreInterface;
use Psr\Clock\ClockInterface;

class DoctrineEventStoreFactory implements EventStoreFactoryInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /** @param array<string, mixed> $options */
    public function build(ContentRepositoryId $contentRepositoryId, array $options, ClockInterface $clock): EventStoreInterface
    {
        // We create a new connection instance in order to avoid nested transactions
        $connection = DriverManager::getConnection($this->entityManager->getConnection()->getParams(), $this->entityManager->getConfiguration(), $this->entityManager->getEventManager());
        return new DoctrineEventStore(
            $connection,
            self::databaseTableName($contentRepositoryId),
            $clock
        );
    }

    public static function databaseTableName(ContentRepositoryId $contentRepositoryId): string
    {
        return sprintf('cr_%s_events', $contentRepositoryId->value);
    }
}
