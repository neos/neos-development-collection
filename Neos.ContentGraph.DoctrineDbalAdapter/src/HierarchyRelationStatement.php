<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayers;
use Neos\ContentRepository\Dbal\Query\Parameter;
use Neos\ContentRepository\Dbal\Query\Parameters;
use Neos\ContentRepository\Dbal\Query\SqlStatementInterface;

/**
 * @internal
 */
final readonly class HierarchyRelationStatement implements SqlStatementInterface
{
    /**
     * @param array<string> $whereClauses
     */
    private function __construct(
        private ContentGraphTableNames $tableNames,
        private ContentStreamLayers $contentStreamLayers,
        private array $whereClauses,
    ) {
    }

    public static function create(ContentGraphTableNames $tableNames, ContentStreamLayers $contentStreamLayers): self
    {
        return new self($tableNames, $contentStreamLayers, []);
    }

    public function where(string $where): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            whereClauses: $where === '' ? [] : [$where],
        );
    }

    public function andWhere(string $where): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            whereClauses: [...$this->whereClauses, ...($where === '' ? [] : [$where])],
        );
    }

    public function getParameters(): Parameters
    {
        return Parameters::create(
            Parameter::integerArray('contentStreamLayers', $this->contentStreamLayers->toIntArray())
        );
    }

    public function toSql(): string
    {
        $additionalWhereClauses = $this->whereClauses === [] ? '' : sprintf("  WHERE %s\n", join("\n  AND ", $this->whereClauses));

        return <<<SQL
            (SELECT h.*
              FROM {$this->tableNames->hierarchyRelation()} AS h
              INNER JOIN (
                SELECT id, MAX(contentstreamlayer) AS contentstreamlayer
                  FROM {$this->tableNames->hierarchyRelation()} FORCE INDEX (UNIQ_id_layer)
                    WHERE (contentstreamlayer IN (:contentStreamLayers))
                GROUP BY id
              ) AS readHierarchy
                ON h.id = readHierarchy.id AND h.contentstreamlayer = readHierarchy.contentstreamlayer
            {$additionalWhereClauses
            })
            SQL;
    }
}
