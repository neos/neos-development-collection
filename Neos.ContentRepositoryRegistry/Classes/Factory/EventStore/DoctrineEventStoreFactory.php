<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Factory\EventStore;

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManagerInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\EventStore\DoctrineAdapter\DoctrineEventStore;
use Psr\Clock\ClockInterface;

class DoctrineEventStoreFactory implements EventStoreFactoryInterface
{
    /**
     * @var array<string, DoctrineEventStore> Runtime cache for created event store instances to prevent too many connections
     */
    private static array $instances = [];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /** @param array<string, mixed> $options */
    public function build(ContentRepositoryId $contentRepositoryId, array $options, ClockInterface $clock): DoctrineEventStore
    {
        $dsn = $options['dsn'] ?? null;
        $hash = md5($contentRepositoryId->value . '|' . $clock::class . '|' . $dsn);
        if (!array_key_exists($hash, self::$instances)) {
            if ($dsn !== null) {
                $connection = DriverManager::getConnection(['url' => $dsn]);
            } else {
                // We create a new connection instance in order to avoid nested transactions
                $connection = DriverManager::getConnection($this->entityManager->getConnection()->getParams(), $this->entityManager->getConfiguration(), $this->entityManager->getEventManager());
            }
            self::$instances[$hash] = new DoctrineEventStore(
                $connection,
                self::databaseTableName($contentRepositoryId),
                $clock
            );
        }
        return self::$instances[$hash];
    }

    public static function databaseTableName(ContentRepositoryId $contentRepositoryId): string
    {
        return sprintf('cr_%s_events', $contentRepositoryId->value);
    }
}
