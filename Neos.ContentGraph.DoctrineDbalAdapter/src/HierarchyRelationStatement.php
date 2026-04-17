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

    public function allContentStreams(): self
    {
        return new self(
            tableNames: $this->tableNames,
            restrictContentStreamLayers: false,
            whereClauses: $this->whereClauses,
        );
    }

    public function toSql(): string
    {
        $whereClause = $this->restrictContentStreamLayers ? "            WHERE (contentstreamlayer IN (:contentStreamLayers))\n" : '';

        $additionalWhereClauses = $this->whereClauses === [] ? '' : sprintf("    WHERE %s\n", join("\n    AND ", $this->whereClauses));

        return <<<SQL
        (SELECT h.*
            FROM {$this->tableNames->hierarchyRelation()} as h
            INNER JOIN (
                SELECT id, MAX(contentstreamlayer) as contentstreamlayer
                    FROM {$this->tableNames->hierarchyRelation()}
        {$whereClause
        }        GROUP BY id
            ) AS activeLayer
                ON h.id = activeLayer.id AND h.contentstreamlayer = activeLayer.contentstreamlayer
        {$additionalWhereClauses
        })
        SQL;
    }
}
