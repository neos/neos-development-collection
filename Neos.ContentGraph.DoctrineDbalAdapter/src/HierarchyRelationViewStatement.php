<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;

/**
 * @internal
 */
final readonly class HierarchyRelationViewStatement
{
    /**
     * @param array<string> $whereClauses
     */
    private function __construct(
        private ContentGraphTableNames $tableNames,
        private WorkspaceName $workspaceName,
        private array $whereClauses,
    ) {
    }

    public static function for(WorkspaceName $workspaceName, ContentGraphTableNames $tableNames): self
    {
        return new self($tableNames, $workspaceName, []);
    }

    public function where(string $where): self
    {
        return new self(
            tableNames: $this->tableNames,
            workspaceName: $this->workspaceName,
            whereClauses: $where === '' ? [] : [$where],
        );
    }

    public function andWhere(string $where): self
    {
        return new self(
            tableNames: $this->tableNames,
            workspaceName: $this->workspaceName,
            whereClauses: [...$this->whereClauses, ...($where === '' ? [] : [$where])],
        );
    }

    public function toSql(): string
    {
        $additionalWhereClauses = $this->whereClauses === [] ? '' : sprintf("    WHERE %s\n", join("\n    AND ", $this->whereClauses));

        return <<<SQL
        (SELECT h.*
            FROM {$this->tableNames->hierarchyRelation()} AS h
            INNER JOIN {$this->tableNames->hierarchyRelationForWorkspace($this->workspaceName)} AS readHierarchy
                ON h.id = readHierarchy.id AND h.contentstreamlayer = readHierarchy.contentstreamlayer
        {$additionalWhereClauses
        })
        SQL;
    }
}
