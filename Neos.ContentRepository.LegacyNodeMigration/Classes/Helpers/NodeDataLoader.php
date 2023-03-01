<?php
declare(strict_types=1);

namespace Neos\ContentRepository\LegacyNodeMigration\Helpers;

use Doctrine\DBAL\Connection;
use Traversable;

final class NodeDataLoader implements \IteratorAggregate
{

    public function __construct(
        private readonly Connection $connection,
    ) {}

    public function getIterator(): Traversable
    {
        $whereClause = [
            'workspace' => $this->connection->quoteIdentifier("workspace") . ' = ' . $this->connection->quote("live"),
            'path' => $this->connection->quoteIdentifier("path") . ' != ' . $this->connection->quote("/"),
            'movedTo' => $this->connection->quoteIdentifier("movedto") . ' IS NULL ',
            'removed' => $this->connection->quoteIdentifier("removed") . ' = ' . ($this->connection->getDatabasePlatform()->getName() === 'postgresql' ? 'FALSE' : '0')
        ];

        $query = $this->connection->executeQuery('
            SELECT
                *
            FROM
                neos_contentrepository_domain_model_nodedata
            WHERE
                ' . $whereClause['workspace'] . ' AND
                ' . $whereClause['path'] . ' AND
            (' . $whereClause['movedTo'] . ' OR ' . $whereClause['removed'] . ')
            ORDER BY
                parentpath, sortingindex, path
        ');
        return $query->iterateAssociative();
    }
}


