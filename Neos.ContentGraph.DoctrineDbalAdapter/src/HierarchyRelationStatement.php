<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayers;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Dbal\Query\Parameter;
use Neos\ContentRepository\Dbal\Query\Parameters;
use Neos\ContentRepository\Dbal\Query\SqlTableSubqueryInterface;

/**
 * @internal
 */
final readonly class HierarchyRelationStatement implements SqlTableSubqueryInterface
{
    /**
     * @param list<string> $whereClauses
     * @param list<string> $innerWhereClauses
     */
    private function __construct(
        private ContentGraphTableNames $tableNames,
        private ContentStreamLayers $contentStreamLayers,
        private DimensionSpacePointSet $dimensionSpacePoints,
        private NodeRelationAnchorPoint|NodeAggregateIdClause|null $childNodeAnchor,
        private NodeRelationAnchorPoint|NodeAggregateIdClause|null $parentNodeAnchor,
        private array $whereClauses,
        private array $innerWhereClauses,
    ) {
    }

    public static function create(ContentGraphTableNames $tableNames, ContentStreamLayers $contentStreamLayers): self
    {
        return new self($tableNames,
            $contentStreamLayers,
            DimensionSpacePointSet::fromArray([]),
            null,
            null,
            [],
            [],
        );
    }

    public function withDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: DimensionSpacePointSet::fromArray([$dimensionSpacePoint]),
            childNodeAnchor: $this->childNodeAnchor,
            parentNodeAnchor: $this->parentNodeAnchor,
            whereClauses: $this->whereClauses,
            innerWhereClauses: $this->innerWhereClauses,
        );
    }

    public function withDimensionSpacePoints(DimensionSpacePointSet $dimensionSpacePoints): self
    {
        if ($dimensionSpacePoints->isEmpty()) {
            throw new \InvalidArgumentException('Dimension space points to filter must not be empty', 1781553616);
        }
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: $dimensionSpacePoints,
            childNodeAnchor: $this->childNodeAnchor,
            parentNodeAnchor: $this->parentNodeAnchor,
            whereClauses: $this->whereClauses,
            innerWhereClauses: $this->innerWhereClauses,
        );
    }

    public function withChildNodeRelationAnchor(NodeRelationAnchorPoint $childNodeRelationAnchorPoint): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: $this->dimensionSpacePoints,
            childNodeAnchor: $childNodeRelationAnchorPoint,
            parentNodeAnchor: $this->parentNodeAnchor,
            whereClauses: $this->whereClauses,
            innerWhereClauses: $this->innerWhereClauses,
        );
    }

    public function withChildNodeAggregateIdPrefilter(NodeAggregateIdClause $childNodeAggregateIdClause): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: $this->dimensionSpacePoints,
            childNodeAnchor: $childNodeAggregateIdClause,
            parentNodeAnchor: $this->parentNodeAnchor,
            whereClauses: $this->whereClauses,
            innerWhereClauses: $this->innerWhereClauses,
        );
    }

    public function withParentNodeRelationAnchor(NodeRelationAnchorPoint $parentNodeRelationAnchorPoint): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: $this->dimensionSpacePoints,
            childNodeAnchor: $this->childNodeAnchor,
            parentNodeAnchor: $parentNodeRelationAnchorPoint,
            whereClauses: $this->whereClauses,
            innerWhereClauses: $this->innerWhereClauses,
        );
    }

    public function withParentNodeAggregateIdPrefilter(NodeAggregateIdClause $parentNodeAggregateIdClause): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: $this->dimensionSpacePoints,
            childNodeAnchor: $this->childNodeAnchor,
            parentNodeAnchor: $parentNodeAggregateIdClause,
            whereClauses: $this->whereClauses,
            innerWhereClauses: $this->innerWhereClauses,
        );
    }

    public function andWhere(string $where): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: $this->dimensionSpacePoints,
            childNodeAnchor: $this->childNodeAnchor,
            parentNodeAnchor: $this->parentNodeAnchor,
            whereClauses: [...$this->whereClauses, ...($where === '' ? [] : [$where])],
            innerWhereClauses: $this->innerWhereClauses,
        );
    }

    public function andInnerWhereRelationIdMatches(string $innerWhere): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: $this->dimensionSpacePoints,
            childNodeAnchor: $this->childNodeAnchor,
            parentNodeAnchor: $this->parentNodeAnchor,
            whereClauses: $this->whereClauses,
            innerWhereClauses: [...$this->innerWhereClauses, ...($innerWhere === '' ? [] : [$innerWhere])],
        );
    }

    public function getParameters(): Parameters
    {
        return Parameters::create(...array_filter([
            Parameter::integerArray('contentStreamLayers', $this->contentStreamLayers->toIntArray()),
            match (true) {
                $this->dimensionSpacePoints->isEmpty() => null,
                $this->dimensionSpacePoints->count() === 1 => Parameter::string('dimensionSpacePointHash', $this->dimensionSpacePoints->getPointHashes()[0]),
                default => Parameter::stringArray('dimensionSpacePointHashes', $this->dimensionSpacePoints->getPointHashes()),
            },
            ...$this->childNodeAnchor ? match ($this->childNodeAnchor::class) {
                NodeRelationAnchorPoint::class => [Parameter::integer('childNodeRelationAnchorPoint', $this->childNodeAnchor->value)],
                NodeAggregateIdClause::class => iterator_to_array($this->childNodeAnchor->getParameters()),
            } : [],
            ...$this->parentNodeAnchor ? match ($this->parentNodeAnchor::class) {
                NodeRelationAnchorPoint::class => [Parameter::integer('parentNodeRelationAnchorPoint', $this->parentNodeAnchor->value)],
                NodeAggregateIdClause::class => iterator_to_array($this->parentNodeAnchor->getParameters()),
            } : [],
        ]));
    }

    public function toSql(): string
    {
        $innerWhereClauses = $this->innerWhereClauses;
        $outerWhereClauses = $this->whereClauses;

        $dimensionWhereClause = match (true) {
            $this->dimensionSpacePoints->isEmpty() => null,
            $this->dimensionSpacePoints->count() === 1 => 'h.dimensionspacepointhash = :dimensionSpacePointHash',
            default => 'h.dimensionspacepointhash IN (:dimensionSpacePointHashes)',
        };

        if ($dimensionWhereClause) {
            $outerWhereClauses[] = $dimensionWhereClause;
            $innerWhereClauses[] = $dimensionWhereClause;
        }

        if ($this->childNodeAnchor instanceof NodeRelationAnchorPoint) {
            $outerWhereClauses[] = $childNodeRelationAnchorPointWhereClause = 'h.childnodeanchor = :childNodeRelationAnchorPoint';
            $innerWhereClauses[] = $childNodeRelationAnchorPointWhereClause;
        }

        if ($this->childNodeAnchor instanceof NodeAggregateIdClause) {
            $innerWhereClauses[] = "h.childnodeanchor IN {$this->childNodeAnchor->toRelationAnchorPointSubquerySql($this->tableNames)}";
            // We don't actually ensure the outer result only contains hierarchies for this node
        }

        if ($this->parentNodeAnchor instanceof NodeRelationAnchorPoint) {
            $outerWhereClauses[] = $parentNodeRelationAnchorPointWhereClause = 'h.parentnodeanchor = :parentNodeRelationAnchorPoint';
            $innerWhereClauses[] = $parentNodeRelationAnchorPointWhereClause;
        }

        if ($this->parentNodeAnchor instanceof NodeAggregateIdClause) {
            $innerWhereClauses[] = "h.parentnodeanchor IN {$this->parentNodeAnchor->toRelationAnchorPointSubquerySql($this->tableNames)}";
            // We don't actually ensure the outer result only contains hierarchies for this node
        }

        $innerWhereClauseSql = $innerWhereClauses === [] ? '' : sprintf(
            <<<SQL
                    AND id IN (
                      SELECT id FROM {$this->tableNames->hierarchyRelation()} AS h
                        WHERE %s
                    )
            
            SQL,
            join("\n            AND ", $innerWhereClauses)
        );
        $outerWhereClauseSql = $outerWhereClauses === [] ? '' : sprintf("  WHERE %s\n", join("\n  AND ", $outerWhereClauses));

        return <<<SQL
            (SELECT h.*
              FROM {$this->tableNames->hierarchyRelation()} AS h
              INNER JOIN (
                SELECT id, MAX(contentstreamlayer) AS contentstreamlayer
                  FROM {$this->tableNames->hierarchyRelation()}
                    WHERE (contentstreamlayer IN (:contentStreamLayers))
            {$innerWhereClauseSql
            }    GROUP BY id
              ) AS readHierarchy
                ON h.id = readHierarchy.id AND h.contentstreamlayer = readHierarchy.contentstreamlayer
            {$outerWhereClauseSql
            })
            SQL;
    }
}
