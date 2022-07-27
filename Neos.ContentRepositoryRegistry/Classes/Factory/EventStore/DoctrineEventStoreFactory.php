<?php
declare(strict_types=1);
namespace Neos\ContentRepositoryRegistry\Factory\EventStore;

use Doctrine\DBAL\Connection;
use Neos\ContentRepositoryRegistry\ValueObject\ContentRepositoryIdentifier;
use Neos\EventStore\DoctrineAdapter\DoctrineEventStore;
use Neos\EventStore\EventStoreInterface;

class DoctrineEventStoreFactory implements EventStoreFactoryInterface
{
    public function __construct(
        private readonly Connection $connection,
    )
    {
    }

    public function build(ContentRepositoryIdentifier $contentRepositoryIdentifier, array $eventStoreSettings): EventStoreInterface
    {
        return new DoctrineEventStore(
            $this->connection,
            $eventStoreSettings['options']['eventTableName'] ?? sprintf('neos_cr_%s_events', $contentRepositoryIdentifier)
        );
    }
}
