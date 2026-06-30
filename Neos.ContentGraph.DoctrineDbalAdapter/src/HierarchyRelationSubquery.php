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
 * SQL builder that resolves the correct `graph_hierarchyrelation` rows from a set of content stream layers.
 *
 * NOTE: the generated SQL only works when the caller binds a prepared statement parameter `:contentStreamLayers`
 * holding the content stream layers of the current content stream (read from `..._graph_contentstreamlayer`).
 *
 *
 * CONCEPT
 * =======
 *
 * Starting with Neos 9.2, each Content Stream consists of (hierarchy relation) *layers*: the topmost layer is
 * (usually) mutable, the layers below are immutable. To read a hierarchy relation we must take the TOP-MOST
 * ContentStreamLayer that exists for a given Hierarchy Relation ID. Layers use an auto-increment, so "top-most"
 * means the highest `contentstreamlayer` for that hierarchy-relation-edge.
 *
 * Conceptually, for every hierarchy relation we need:
 *
 *     SELECT MAX(contentstreamlayer) WHERE id = ... AND contentstreamlayer IN (:contentStreamLayers)
 *
 * Tombstones: node removal writes a tombstone row (same id, highest contentstreamlayer, every other column NULL).
 * Because the tombstone has the highest layer it WINS the MAX resolution, so the removed node correctly drops out
 * (the caller's outer WHERE then discards the NULL row). Any change to the layer resolution must preserve this.
 *
 *
 * ATTEMPT 1 (replaced): MAX(...) GROUP BY id in a derived table
 * =============================================================
 *
 * This approach was fast in MariaDB but slow in MySQL.
 *
 * The approach explained step by step:
 *
 * 1) We essentially want to run `SELECT MAX(contentstreamlayer) WHERE id = ... AND contentstreamlayer IN (:contentStreamLayers)`
 *    once per id, but more efficiently. To resolve a whole set of ids in one query, we turn the per-id MAX into a grouped one:
 *
 *        SELECT id, MAX(contentstreamlayer) AS contentstreamlayer
 *          FROM ..._graph_hierarchyrelation
 *          WHERE contentstreamlayer IN (:contentStreamLayers)
 *          GROUP BY id
 *
 *    This yields one (id, winning-layer) pair per id.
 *
 * 2) That pair only tells us which layer wins (for a given id); we still need the full row `h.*`. So we treat the
 *    grouped result as a derived table `readHierarchy` and INNER JOIN the original table back onto it, matching on
 *    (id, winning-layer). The INNER JOIN also drops any id that had no row in the allowed layers.
 *
 * 3) Finally we add pre-filters as performance improvements for MariaDB: an inner WHERE inside the derived table
 *    (so MariaDB groups far fewer records, pushed down via {@see withPossibleWhereCondition()}) and an outer WHERE
 *    on the joined result for the caller's own conditions (via {@see withWhereCondition()}).
 *
 * WHY IT WAS REPLACED: MySQL cannot optimize this. The derived table `readHierarchy` has `GROUP BY id`, and MySQL
 * has no way to push the outer join key (id) into a grouped derived table, so it materializes the full grouped
 * result for every lookup. MariaDB has the `split_materialized` optimization, which pushes the wanted id INTO the
 * grouped derived table and thus computes MAX only for the wanted ids via index — MySQL has no equivalent.
 *
 *
 * ATTEMPT 2 (current): anti-join via NOT EXISTS
 * =============================================
 *
 * Restating Attempt 1 mathematically, with L = the allowed layers (:contentStreamLayers):
 * - for each id, compute m(id) = MAX{ layer : layer ∈ L }
 * - keep row h where h.id = id AND h.layer = m(id)         -- i.e. the greatest layer per id
 * - in symbols: h.layer ∈ L  ∧  h.layer = max{ layer ∈ L for id }
 *
 * This is equivalent to: "no row of the same id has a layer in L strictly greater than h.layer", i.e.
 *
 *     h.layer ∈ L  ∧  ¬∃ same-id row with (layer ∈ L ∧ layer > h.layer)
 *
 * which is exactly an anti-join (NOT EXISTS):
 *
 *     SELECT h.* FROM hr h
 *      WHERE h.layer IN L [AND id-pushdown]
 *        AND NOT EXISTS (
 *            -- exists a row with same id and bigger layer? -> then the current row is NOT the highest
 *            -- layer and we DISCARD it.
 *            SELECT 1 FROM hr hWin
 *            WHERE hWin.id = h.id AND hWin.layer IN L AND hWin.layer > h.layer)
 *
 * NOTE: both forms are equivalent regardless of indexing. The unique index UNIQ_id_layer(id, layer) just guarantees
 * one winning row per id (without it both forms return all rows tied at the winning layer) and makes the anti-join fast.
 *
 * The NOT EXISTS form stays flat and mergeable, lets the optimizer push predicates and use the
 * `UNIQ_id_layer (id, contentstreamlayer)` index, and avoids materialization on both MySQL and MariaDB.
 *
 *
 * TOMBSTONE / MOVE SAFETY OF THE PUSHDOWN
 * =======================================
 *
 * The "possible" where condition ({@see withPossibleWhereCondition()}, {@see withPossibleChildNodeAggregateId()},
 * {@see withPossibleParentNodeAggregateId()}) is pushed into the layer resolution as `id IN (SELECT id FROM hr WHERE ...)`.
 * Filtering by `id` (not directly on the candidate rows) is the only tombstone-safe and move-safe way to prefilter:
 *
 * - Tombstone-safe: it keeps *all* layers of every candidate relation in the layer resolution, including the removal
 *   tombstone (NULL anchors/dsp but same id and highest layer), so the winning layer stays exact and removed nodes
 *   correctly drop out. A predicate filtering the candidate rows directly on a nullable column (childnodeanchor,
 *   parentnodeanchor, dimensionspacepointhash) would exclude the tombstone and resurrect the deleted node.
 * - Move-safe: a single anchor is not layer-invariant for a relation id (copy-on-write reassigns the child anchor
 *   across layers, a move reassigns the parent); the id-based superset still keeps every layer of the matching
 *   relations, and the caller's outer WHERE ({@see withWhereCondition()}) trims the extras.
 *
 * This is purely a prefilter (like a bloom filter): it MAY keep more rows than the caller ultimately wants, but it must
 * NEVER keep fewer.
 *
 * @internal
 */
final readonly class HierarchyRelationSubquery implements SqlTableSubqueryInterface
{
    private function __construct(
        private ContentGraphTableNames $tableNames,
        private ContentStreamLayers $contentStreamLayers,
        private DimensionSpacePointSet $dimensionSpacePoints,
        private NodeRelationAnchorPoint|NodeAggregateIdCondition|ReferenceDestinationNodeAggregateIdCondition|null $childNodeAnchor,
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
    public function withPossibleChildNodeAggregateId(NodeAggregateIdCondition|ReferenceDestinationNodeAggregateIdCondition $possibleChildNodeAggregateIdCondition): self
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
        $parameters = [
            Parameter::integerArray('contentStreamLayers', $this->contentStreamLayers->toIntArray())
        ];

        if ($this->whereCondition !== null) {
            $parameters = [...$parameters, ...iterator_to_array($this->whereCondition->getParameters())];
        }

        if ($this->possibleWhereCondition !== null) {
            $parameters = [...$parameters, ...iterator_to_array($this->possibleWhereCondition->getParameters())];
        }

        $dimensionSpacePointsParameter = match (true) {
            $this->dimensionSpacePoints->isEmpty() => null,
            $this->dimensionSpacePoints->count() === 1 => Parameter::string('dimensionSpacePointHash', $this->dimensionSpacePoints->getPointHashes()[0]),
            default => Parameter::stringArray('dimensionSpacePointHashes', $this->dimensionSpacePoints->getPointHashes()),
        };

        if ($dimensionSpacePointsParameter !== null) {
            $parameters[] = $dimensionSpacePointsParameter;
        }

        if ($this->childNodeAnchor instanceof NodeRelationAnchorPoint) {
            $parameters[] = Parameter::integer('childNodeRelationAnchorPoint', $this->childNodeAnchor->value);
        }

        if ($this->childNodeAnchor instanceof NodeAggregateIdCondition || $this->childNodeAnchor instanceof ReferenceDestinationNodeAggregateIdCondition) {
            $parameters = [...$parameters, ...iterator_to_array($this->childNodeAnchor->getParameters())];
        }

        if ($this->parentNodeAnchor instanceof NodeRelationAnchorPoint) {
            $parameters[] = Parameter::integer('parentNodeRelationAnchorPoint', $this->parentNodeAnchor->value);
        }

        if ($this->parentNodeAnchor instanceof NodeAggregateIdCondition) {
            $parameters = [...$parameters, ...iterator_to_array($this->parentNodeAnchor->getParameters())];
        }

        return Parameters::create(...$parameters);
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

        if ($this->childNodeAnchor instanceof NodeAggregateIdCondition || $this->childNodeAnchor instanceof ReferenceDestinationNodeAggregateIdCondition) {
            $possibleWhereConditions[] = "h.childnodeanchor IN {$this->childNodeAnchor->toRelationAnchorPointSubquerySql($this->tableNames)}";
            // We don't actually ensure the final result only contains hierarchies for this node
        }

        if ($this->parentNodeAnchor instanceof NodeRelationAnchorPoint) {
            $whereConditions[] = $parentNodeRelationAnchorPointWhereCondition = 'h.parentnodeanchor = :parentNodeRelationAnchorPoint';
            $possibleWhereConditions[] = $parentNodeRelationAnchorPointWhereCondition;
        }

        if ($this->parentNodeAnchor instanceof NodeAggregateIdCondition) {
            $possibleWhereConditions[] = "h.parentnodeanchor IN {$this->parentNodeAnchor->toRelationAnchorPointSubquerySql($this->tableNames)}";
            // We don't actually ensure the final result only contains hierarchies for this node
        }

        // pushed into the layer resolution as `id IN (...)` so the prefilter stays tombstone-safe (see class docblock)
        $possibleWhereConditionSql = $possibleWhereConditions === [] ? '' : sprintf(
            "\n    AND id IN (\n      SELECT id FROM %s AS h\n        WHERE %s\n    )",
            $this->tableNames->hierarchyRelation(),
            join("\n        AND ", $possibleWhereConditions)
        );
        // applied to the already layer-resolved hierarchy relation `h`
        $whereConditionSql = $whereConditions === [] ? '' : sprintf("\n  AND %s", join("\n  AND ", $whereConditions));

        return <<<SQL
            (SELECT h.*
              FROM {$this->tableNames->hierarchyRelation()} AS h
              WHERE (h.contentstreamlayer IN (:contentStreamLayers)){$possibleWhereConditionSql}
                AND NOT EXISTS (
                  SELECT 1
                    FROM {$this->tableNames->hierarchyRelation()} hWin
                    WHERE hWin.id = h.id
                      AND hWin.contentstreamlayer IN (:contentStreamLayers)
                      AND hWin.contentstreamlayer > h.contentstreamlayer
                ){$whereConditionSql}
            )
            SQL;
    }
}
