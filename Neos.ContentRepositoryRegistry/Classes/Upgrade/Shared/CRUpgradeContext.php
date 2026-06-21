<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\Shared;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Factory\ContentRepositoryServiceInterface;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\EventStore\DoctrineAdapter\DoctrineEventStore;
use Psr\Clock\ClockInterface;

/**
 * @internal CR upgrade internals
 */
final readonly class CRUpgradeContext implements ContentRepositoryServiceInterface
{
    public function __construct(
        public ContentRepositoryId $contentRepositoryId,
        public DoctrineEventStore $doctrineEventStore,
        public Connection $dbal,
        public string $eventStoreTableName,
        public ClockInterface $clock,
    ) {
    }
}
