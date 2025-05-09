<?php
declare(strict_types=1);

namespace Neos\ContentRepository\LegacyNodeMigration\Helpers;

use Doctrine\DBAL\Connection;

/**
 * @implements \IteratorAggregate<int, array<string, mixed>>
 */
final class DomainDataLoader implements \IteratorAggregate
{
    public function __construct(
        private readonly Connection $connection,
    ) {
    }

    /**
     * @return \Traversable<int, array<string, mixed>>
     */
    public function getIterator(): \Traversable
    {
        $query = $this->connection->executeQuery('
            SELECT
                *
            FROM
                neos_neos_domain_model_domain
        ');
        return $query->iterateAssociative();
    }
}


