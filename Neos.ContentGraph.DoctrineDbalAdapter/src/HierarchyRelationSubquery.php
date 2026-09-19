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
 * SQL builder that resolves the correct hierarchy-relation rows for a set of content stream layers, and further conditions.
 *
 * CONCEPT
 * =======
 *
 * Starting with Neos 9.2, each Content Stream consists of (hierarchy relation) *layers* {@see ContentStreamLayers}:
 * To read a hierarchy relation we must take the TOP-MOST ContentStreamLayer that exists for a given Hierarchy Relation ID.
 * Layers use an auto-increment, so "top-most" means the highest `contentstreamlayer` for that hierarchy-relation-edge.
 *
 * Conceptually, for every hierarchy relation we need:
 *
 *     SELECT MAX(contentstreamlayer) WHERE id = ... AND contentstreamlayer IN (:contentStreamLayers)
 *
 * Documentation visualized with graphs {@link https://github.com/neos/neos-development-collection/pull/5776}
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
 *          FROM {hierarchyRelation()}
 *          WHERE contentstreamlayer IN (:contentStreamLayers)
 *          GROUP BY id
 *
 *    This yields one (id, winning-layer) pair per id.
 *
 * 2) That pair only tells us which layer wins (for a given id); we still need the full row `h.*`. Selecting any other columns
 *    is undefined behavior and forbidden in strict mode via only_full_group_by. Thus treat the grouped result as a derived table
 *    `readHierarchy` and INNER JOIN the original table back onto it, matching on (id, winning-layer).
 *    The INNER JOIN also drops any id that had no row in the allowed layers.
 *
 * WHY IT WAS REPLACED: MySQL cannot optimize this. The derived table `readHierarchy` has `GROUP BY id`, and MySQL
 * has no way to push the outer join key (id) into a grouped derived table, so it materializes the full grouped
 * result for every lookup.
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
 *      WHERE h.layer IN L [AND id-prefilter]
 *        AND NOT EXISTS (
 *            -- exists a row with same id and bigger layer? -> then the current row is NOT the highest
 *            -- layer and we DISCARD it.
 *            SELECT 1 FROM hr hWin
 *            WHERE hWin.id = h.id AND hWin.layer IN L AND hWin.layer > h.layer)
 *
 * The NOT EXISTS form stays flat and mergeable, lets the optimizer push predicates and use the
 * `UNIQ_id_layer (id, contentstreamlayer)` index, and avoids materialization on both MySQL and MariaDB.
 *
 *
 * Optimisation: Pre-filtering
 * ===========================
 *
 * To reduce the amount of hierarchy rows to consider in subsequent joins and also before applying the NOT EXIST anti-join,
 * we pre-filter the whole hierarchy table via cheaper conditions.
 *
 * These "possible" where condition are in effect when using all regular conditions like {@see withDimensionSpacePoint()}
 * and {@see withChildNodeRelationAnchor()} or other variations.
 *
 * To explicitly force a prefiltering which cannot be inferred from the regular conditions given, {@see withPossibleChildNodeAggregateId()},
 * {@see withPossibleParentNodeAggregateId()}) as well as {@see withPossibleWhereCondition()} can be used.
 *
 * **We MUST only pre-filter by the `id` column (not directly on the candidate rows)**
 *
 * Using any other columns would result in undefined behavior, because they are not layer invariant:
 *
 * - `parentnodeanchor` see move node
 * - `childnodeanchor` see copy on write (set properties)
 *   - in theory its safe to access the nodes' node aggregate id which MUST be invariant across all layers
 * - `dimensionspacepointhash` see move dimension space point
 * - `subtreetags` see subtree tagging
 * - `position` see move node
 *
 * Additionally, all of these columns can be set to `NULL` signaling a node removal.
 * Filtering on these candidates would exclude the NULL values and resurrect the node.
 *
 * The pre-filtering leverages a sub-select on the hierarchy relation table to find all possible hierarchy ids:
 *
 *     AND id IN (
 *       SELECT id FROM {hierarchyRelation()} h
 *         WHERE h.dimensionspacepointhash = :dimensionSpacePointHash
 *         AND h.childNodeAnchor = :childNodeAnchor
 *         [...further prefilter]
 *     )
 *
 * The id-based superset still keeps every layer of a possibly matching relations. The outer WHERE is responsible to trim the extras.
 *
 * This is purely a prefilter (like a bloom filter): it MAY keep more rows than needed,
 * but it must NEVER keep fewer.
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
        return new self(
            $tableNames,
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
     *
     * See documentation: "Optimisation: Pre-filtering"
     *
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
     *
     * See documentation: "Optimisation: Pre-filtering"
     *
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
     *
     * See documentation: "Optimisation: Pre-filtering"
     *
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

        // pushed into the layer resolution as `id IN (...)` so the prefilter only relies on the layer invariant id column
        $possibleWhereConditionSql = $possibleWhereConditions === [] ? '' : sprintf(
            "\n    AND id IN (\n      SELECT id FROM %s AS h\n        WHERE %s\n    )",
            $this->tableNames->hierarchyRelation(),
            join("\n        AND ", $possibleWhereConditions)
        );
        // applied to the already layer-resolved hierarchy relation
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
