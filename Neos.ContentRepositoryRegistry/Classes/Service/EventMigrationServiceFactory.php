<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Service;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryDependencies;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceFactoryInterface;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepositoryRegistry\Command\MigrateEventsCommandController;
use Neos\EventStore\DoctrineAdapter\DoctrineEventStore;
use Neos\Flow\Annotations as Flow;

/**
 * Factory for the {@see EventMigrationService}
 *
 * @implements ContentRepositoryServiceFactoryInterface<EventMigrationService>
 * @internal this is currently only used by the {@see MigrateEventsCommandController}
 */
#[Flow\Scope("singleton")]
final class EventMigrationServiceFactory implements ContentRepositoryServiceFactoryInterface
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    public function build(ContentRepositoryServiceFactoryDependencies $serviceFactoryDependencies): ContentRepositoryServiceInterface
    {
        if (!($serviceFactoryDependencies->eventStore instanceof DoctrineEventStore)) {
            throw new \RuntimeException('EventMigrationService only works with DoctrineEventStore, ' . get_class($serviceFactoryDependencies->eventStore) . ' given');
        }

        return new EventMigrationService(
            $serviceFactoryDependencies->contentRepositoryId,
            $serviceFactoryDependencies->eventStore,
            $this->connection
        );
    }
}
