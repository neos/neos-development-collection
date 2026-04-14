<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

/**
 * @internal
 */
final readonly class HierarchyRelationStatement
{
    private function __construct(
        private ContentGraphTableNames $tableNames,
        private string $where,
    ) {
    }

    public static function for(ContentGraphTableNames $tableNames): self
    {
        return new self($tableNames, '');
    }

    public function where(string $where): self
    {
        return new self(
            tableNames: $this->tableNames,
            where: $where,
        );
    }

    public function toSql(): string
    {
        $additionalWhereClauses = $this->where !== '' ? "    WHERE {$this->where}\n" : '';

        return <<<SQL
        (SELECT h.*
            FROM {$this->tableNames->hierarchyRelation()} as h
            INNER JOIN (
                SELECT id, MAX(contentstreamlayer) as contentstreamlayer
                    FROM {$this->tableNames->hierarchyRelation()}
                    WHERE (contentstreamlayer IN (:contentStreamLayers))
                GROUP BY id
            ) AS activeLayer 
                ON h.id = activeLayer.id AND h.contentstreamlayer = activeLayer.contentstreamlayer
        {$additionalWhereClauses})
        SQL;
    }
}
