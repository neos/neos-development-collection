<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayers;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
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
        private DimensionSpacePointSet $dimensionSpacePoints,
        private ?NodeRelationAnchorPoint $childNodeRelationAnchorPoint,
        private ?NodeRelationAnchorPoint $parentNodeRelationAnchorPoint,
        private array $whereClauses,
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
        );
    }

    public function withDimensionSpacePoint(DimensionSpacePoint $dimensionSpacePoint): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: DimensionSpacePointSet::fromArray([$dimensionSpacePoint]),
            childNodeRelationAnchorPoint: $this->childNodeRelationAnchorPoint,
            parentNodeRelationAnchorPoint: $this->parentNodeRelationAnchorPoint,
            whereClauses: $this->whereClauses,
        );
    }

    public function withDimensionSpacePoints(DimensionSpacePointSet $dimensionSpacePoints): self
    {
        if ($dimensionSpacePoints->isEmpty()) {
            throw new \RuntimeException(sprintf('TODO Invalid'), 1781544419);
        }
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: $dimensionSpacePoints,
            childNodeRelationAnchorPoint: $this->childNodeRelationAnchorPoint,
            parentNodeRelationAnchorPoint: $this->parentNodeRelationAnchorPoint,
            whereClauses: $this->whereClauses,
        );
    }

    public function withChildNodeRelationAnchor(NodeRelationAnchorPoint $childNodeRelationAnchorPoint): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: $this->dimensionSpacePoints,
            childNodeRelationAnchorPoint: $childNodeRelationAnchorPoint,
            parentNodeRelationAnchorPoint: $this->parentNodeRelationAnchorPoint,
            whereClauses: $this->whereClauses,
        );
    }

    public function withParentNodeRelationAnchor(NodeRelationAnchorPoint $parentNodeRelationAnchorPoint): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: $this->dimensionSpacePoints,
            childNodeRelationAnchorPoint: $this->childNodeRelationAnchorPoint,
            parentNodeRelationAnchorPoint: $parentNodeRelationAnchorPoint,
            whereClauses: $this->whereClauses,
        );
    }

    public function where(string $where): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: $this->dimensionSpacePoints,
            childNodeRelationAnchorPoint: $this->childNodeRelationAnchorPoint,
            parentNodeRelationAnchorPoint: $this->parentNodeRelationAnchorPoint,
            whereClauses: $where === '' ? [] : [$where],
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
            $this->childNodeRelationAnchorPoint !== null
                ? Parameter::integer('childNodeRelationAnchorPoint', $this->childNodeRelationAnchorPoint->value)
                : null,
            $this->parentNodeRelationAnchorPoint !== null
                ? Parameter::integer('parentNodeRelationAnchorPoint', $this->parentNodeRelationAnchorPoint->value)
                : null
        ]));
    }

    public function toSql(): string
    {
        $innerWhereClauses = [];
        $outerWhereClauses = $this->whereClauses;

        $dimensionWhereClause = match (true) {
            $this->dimensionSpacePoints->isEmpty() => null,
            $this->dimensionSpacePoints->count() === 1 => 'h.dimensionspacepointhash = :dimensionSpacePointHash',
            default => 'h.dimensionspacepointhash IN (:dimensionSpacePointHashes)',
        };

        if ($this->childNodeRelationAnchorPoint !== null) {
            $outerWhereClauses[] = $childNodeRelationAnchorPointWhereClause = 'h.childnodeanchor = :childNodeRelationAnchorPoint';
            $innerWhereClauses[] = $childNodeRelationAnchorPointWhereClause;
        }

        if ($this->parentNodeRelationAnchorPoint !== null) {
            $outerWhereClauses[] = 'h.parentnodeanchor = :parentNodeRelationAnchorPoint';
            // TODO Illegal optimisation, fails test Features/W8-IndividualNodePublication/01-ConstraintChecks.feature:123
            // $innerWhereClauses[] = $parentNodeRelationAnchorPointWhereClause;
        }

        if ($dimensionWhereClause) {
            $outerWhereClauses[] = $dimensionWhereClause;
            // TODO Optimisation does not work with MoveDimensionSpacePoint because we would need to select the other dimension space point as well
            // $innerWhereClauses[] = $dimensionWhereClause;
        }

        $innerWhereClauseSql = $innerWhereClauses === [] ? '' : sprintf("  AND ((%s) OR h.childnodeanchor IS NULL)\n", join("\n  AND ", $innerWhereClauses));
        $outerWhereClauseSql = $outerWhereClauses === [] ? '' : sprintf("  WHERE %s\n", join("\n  AND ", $outerWhereClauses));

        return <<<SQL
            (SELECT h.*
              FROM {$this->tableNames->hierarchyRelation()} AS h
              INNER JOIN (
                SELECT h.id, MAX(h.contentstreamlayer) AS contentstreamlayer
                  FROM {$this->tableNames->hierarchyRelation()} AS h
                    WHERE (h.contentstreamlayer IN (:contentStreamLayers))
            {$innerWhereClauseSql
            }
                GROUP BY h.id
              ) AS readHierarchy
                ON h.id = readHierarchy.id AND h.contentstreamlayer = readHierarchy.contentstreamlayer
            {$outerWhereClauseSql
            })
            SQL;
    }
}
