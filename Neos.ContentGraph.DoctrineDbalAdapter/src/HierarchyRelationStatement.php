<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

/**
 * @internal
 */
final readonly class HierarchyRelationStatement
{
    /**
     * @param array<string> $whereClauses
     */
    private function __construct(
        private ContentGraphTableNames $tableNames,
        private bool $restrictContentStreamLayers,
        private array $whereClauses,
    ) {
    }

    public static function for(ContentGraphTableNames $tableNames): self
    {
        return new self($tableNames, true, []);
    }

    public function where(string $where): self
    {
        return new self(
            tableNames: $this->tableNames,
            restrictContentStreamLayers: $this->restrictContentStreamLayers,
            whereClauses: $where === '' ? [] : [$where],
        );
    }

    public function andWhere(string $where): self
    {
        return new self(
            tableNames: $this->tableNames,
            restrictContentStreamLayers: $this->restrictContentStreamLayers,
            whereClauses: [...$this->whereClauses, ...($where === '' ? [] : [$where])],
        );
    }

    public function toSql(): string
    {
        $additionalWhereClauses = $this->whereClauses === [] ? '' : sprintf("    WHERE %s\n", join("\n    AND ", $this->whereClauses));

        return <<<SQL
        (SELECT h.*
            FROM {$this->tableNames->hierarchyRelation()} AS h
            INNER JOIN (
                SELECT id, MAX(contentstreamlayer) AS contentstreamlayer
                    FROM {$this->tableNames->hierarchyRelation()}
                        WHERE (contentstreamlayer IN (:contentStreamLayers))
                GROUP BY id
            ) AS readHierarchy
                ON h.id = readHierarchy.id AND h.contentstreamlayer = readHierarchy.contentstreamlayer
        {$additionalWhereClauses
        })
        SQL;
    }
}
