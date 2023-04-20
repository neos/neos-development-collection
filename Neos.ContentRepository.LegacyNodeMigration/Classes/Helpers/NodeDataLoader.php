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
                workspace = "live"
                AND (movedto IS NULL OR removed=0)
                AND path != "/"
            ORDER BY
                -- dimensionshash d7... is {} (the empty dimension).
                -- Because there is a fallback from a dimension value to no dimension value in the old CR (if nothing is found),
                -- we need to ensure that the empty dimensionshash comes LAST.
                -- see NodeDataToEventsProcessor::processNodeData() which handles this special case
                parentpath, sortingindex, path, IF(dimensionshash=\'d751713988987e9331980363e24189ce\', 1, 0)
        ');
        return $query->iterateAssociative();
    }
}


