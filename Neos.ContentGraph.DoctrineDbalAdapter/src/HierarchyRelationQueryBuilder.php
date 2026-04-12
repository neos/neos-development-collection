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
            INNER JOIN (SELECT id, MAX(contentstreamdbid) as contentstreamdbid
                FROM {$this->tableNames->hierarchyRelation()}
                WHERE (contentstreamdbid IN (:contentStreamDbIds))
                GROUP BY id
            ) AS hIds ON h.contentstreamdbid = hIds.contentstreamdbid AND h.id = hIds.id
            {$whereClause}
        )
        SQL;
    }
}
