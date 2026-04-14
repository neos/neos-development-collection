<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

/**
 * @internal
 */
final readonly class HierarchyRelationQueryBuilder
{
    public function __construct(
        public ContentGraphTableNames $tableNames
    ) {
    }

    public function selectHierarchyRowsForContentStream(string $whereClause = ''): string
    {
        return <<<SQL
        (SELECT h.*
            FROM {$this->tableNames->hierarchyRelation()} as h
            INNER JOIN (SELECT id, MAX(contentstreamlayer) as contentstreamlayer
                FROM {$this->tableNames->hierarchyRelation()}
                WHERE (contentstreamlayer IN (:contentStreamLayers))
                GROUP BY id
            ) AS contentStreamLayers
                ON h.id = contentStreamLayers.id
                    AND h.contentstreamlayer = contentStreamLayers.contentstreamlayer
            {$whereClause}
        )
        SQL;
    }
}
