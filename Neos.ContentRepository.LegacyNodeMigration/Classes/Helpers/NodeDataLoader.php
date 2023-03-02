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
        $platform = $this->connection->getDatabasePlatform() ? $this->connection->getDatabasePlatform()->getName() : '';
        $removed = $platform === 'postgresql' ? 'FALSE' : 0;

        $query = $this->connection->executeQuery('
            SELECT
                *
            FROM
                neos_contentrepository_domain_model_nodedata
            WHERE
                "workspace" = \'live\'
                AND ("movedto" IS NULL OR "removed"=:removed)
                AND "path" != \'/\'
            ORDER BY
                parentpath, sortingindex, path
        ', ['removed' => $removed]);

        return $query->iterateAssociative();
    }
}


