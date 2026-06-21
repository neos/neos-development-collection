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
use Neos\ContentRepository\Dbal\Query\SqlWhereConditionInterface;

/**
 * @internal
 */
final readonly class HierarchyRelationSubquery implements SqlTableSubqueryInterface
{
    private function __construct(
        private ContentGraphTableNames $tableNames,
        private ContentStreamLayers $contentStreamLayers,
        private DimensionSpacePointSet $dimensionSpacePoints,
        private NodeRelationAnchorPoint|NodeAggregateIdCondition|null $childNodeAnchor,
        private NodeRelationAnchorPoint|NodeAggregateIdCondition|null $parentNodeAnchor,
        private SqlWhereConditionInterface|null $whereCondition,
        private SqlWhereConditionInterface|null $possibleWhereCondition,
    ) {
    }

    public static function create(ContentGraphTableNames $tableNames, ContentStreamLayers $contentStreamLayers): self
    {
        return new self($tableNames,
            $contentStreamLayers,
            DimensionSpacePointSet::fromArray([]),
            null,
            null,
            null,
            null,
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
            whereCondition: $this->whereCondition,
            possibleWhereCondition: $this->possibleWhereCondition,
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
            whereCondition: $this->whereCondition,
            possibleWhereCondition: $this->possibleWhereCondition,
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
            whereCondition: $this->whereCondition,
            possibleWhereCondition: $this->possibleWhereCondition,
        );
    }

    /**
     * Performant way to exclude additional hierarchy relations.
     *
     * As with a bloom filter, false positive matches are possible, but false negatives are not.
     * The matching hierarchy relations still must be filtered again.
     */
    public function withPossibleChildNodeAggregateId(NodeAggregateIdCondition $possibleChildNodeAggregateIdCondition): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: $this->dimensionSpacePoints,
            childNodeAnchor: $possibleChildNodeAggregateIdCondition,
            parentNodeAnchor: $this->parentNodeAnchor,
            whereCondition: $this->whereCondition,
            possibleWhereCondition: $this->possibleWhereCondition,
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
            whereCondition: $this->whereCondition,
            possibleWhereCondition: $this->possibleWhereCondition,
        );
    }

    /**
     * Performant way to exclude additional hierarchy relations.
     *
     * As with a bloom filter, false positive matches are possible, but false negatives are not.
     * The matching hierarchy relations still must be filtered again.
     */
    public function withPossibleParentNodeAggregateId(NodeAggregateIdCondition $possibleParentNodeAggregateIdCondition): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: $this->dimensionSpacePoints,
            childNodeAnchor: $this->childNodeAnchor,
            parentNodeAnchor: $possibleParentNodeAggregateIdCondition,
            whereCondition: $this->whereCondition,
            possibleWhereCondition: $this->possibleWhereCondition,
        );
    }

    public function withWhereCondition(SqlWhereConditionInterface $whereCondition): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: $this->dimensionSpacePoints,
            childNodeAnchor: $this->childNodeAnchor,
            parentNodeAnchor: $this->parentNodeAnchor,
            whereCondition: $whereCondition,
            possibleWhereCondition: $this->possibleWhereCondition,
        );
    }

    /**
     * Performant way to exclude additional hierarchy relations.
     *
     * As with a bloom filter, false positive matches are possible, but false negatives are not.
     * The matching hierarchy relations still must be filtered again.
     */
    public function withPossibleWhereCondition(SqlWhereConditionInterface $possibleWhereCondition): self
    {
        return new self(
            tableNames: $this->tableNames,
            contentStreamLayers: $this->contentStreamLayers,
            dimensionSpacePoints: $this->dimensionSpacePoints,
            childNodeAnchor: $this->childNodeAnchor,
            parentNodeAnchor: $this->parentNodeAnchor,
            whereCondition: $this->whereCondition,
            possibleWhereCondition: $possibleWhereCondition,
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
                NodeAggregateIdCondition::class => iterator_to_array($this->childNodeAnchor->getParameters()),
            } : [],
            ...$this->parentNodeAnchor ? match ($this->parentNodeAnchor::class) {
                NodeRelationAnchorPoint::class => [Parameter::integer('parentNodeRelationAnchorPoint', $this->parentNodeAnchor->value)],
                NodeAggregateIdCondition::class => iterator_to_array($this->parentNodeAnchor->getParameters()),
            } : [],
        ]));
    }

    public function toSql(): string
    {
        $whereConditions = [];
        $possibleWhereConditions = [];

        if ($this->whereCondition !== null) {
            $whereConditions[] = $this->whereCondition->toWhereSql('h');
        }

        if ($this->possibleWhereCondition !== null) {
            $possibleWhereConditions[] = $this->possibleWhereCondition->toWhereSql('h');
        }

        $dimensionSpacePointsWhereCondition = match (true) {
            $this->dimensionSpacePoints->isEmpty() => null,
            $this->dimensionSpacePoints->count() === 1 => 'h.dimensionspacepointhash = :dimensionSpacePointHash',
            default => 'h.dimensionspacepointhash IN (:dimensionSpacePointHashes)',
        };

        if ($dimensionSpacePointsWhereCondition) {
            $whereConditions[] = $dimensionSpacePointsWhereCondition;
            $possibleWhereConditions[] = $dimensionSpacePointsWhereCondition;
        }

        if ($this->childNodeAnchor instanceof NodeRelationAnchorPoint) {
            $whereConditions[] = $childNodeRelationAnchorPointWhereCondition = 'h.childnodeanchor = :childNodeRelationAnchorPoint';
            $possibleWhereConditions[] = $childNodeRelationAnchorPointWhereCondition;
        }

        if ($this->childNodeAnchor instanceof NodeAggregateIdCondition) {
            $possibleWhereConditions[] = "h.childnodeanchor IN {$this->childNodeAnchor->toRelationAnchorPointSubquerySql($this->tableNames)}";
            // We don't actually ensure the outer result only contains hierarchies for this node
        }

        if ($this->parentNodeAnchor instanceof NodeRelationAnchorPoint) {
            $whereConditions[] = $parentNodeRelationAnchorPointWhereCondition = 'h.parentnodeanchor = :parentNodeRelationAnchorPoint';
            $possibleWhereConditions[] = $parentNodeRelationAnchorPointWhereCondition;
        }

        if ($this->parentNodeAnchor instanceof NodeAggregateIdCondition) {
            $possibleWhereConditions[] = "h.parentnodeanchor IN {$this->parentNodeAnchor->toRelationAnchorPointSubquerySql($this->tableNames)}";
            // We don't actually ensure the outer result only contains hierarchies for this node
        }

        $possibleWhereConditionSql = $possibleWhereConditions === [] ? '' : sprintf(
            <<<SQL
                    AND id IN (
                      SELECT id FROM {$this->tableNames->hierarchyRelation()} AS h
                        WHERE %s
                    )
            
            SQL,
            join("\n            AND ", $possibleWhereConditions)
        );
        $whereConditionSql = $whereConditions === [] ? '' : sprintf("  WHERE %s\n", join("\n  AND ", $whereConditions));

        return <<<SQL
            (SELECT h.*
              FROM {$this->tableNames->hierarchyRelation()} AS h
              INNER JOIN (
                SELECT id, MAX(contentstreamlayer) AS contentstreamlayer
                  FROM {$this->tableNames->hierarchyRelation()}
                    WHERE (contentstreamlayer IN (:contentStreamLayers))
            {$possibleWhereConditionSql
            }    GROUP BY id
              ) AS readHierarchy
                ON h.id = readHierarchy.id AND h.contentstreamlayer = readHierarchy.contentstreamlayer
            {$whereConditionSql
            })
            SQL;
    }
}
