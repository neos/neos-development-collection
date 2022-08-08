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
        $query = $this->connection->executeQuery('
            SELECT
                *
            FROM
                neos_contentrepository_domain_model_nodedata
            WHERE
                workspace = \'live\'
                AND (movedto IS NULL OR removed=0)
                AND path != \'/\'
            ORDER BY
                parentpath, sortingindex
        ');
        return $query->iterateAssociative();
    }
}


