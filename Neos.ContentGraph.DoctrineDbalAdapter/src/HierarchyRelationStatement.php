<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

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
 *    (so MariaDB groups far fewer records) and an outer WHERE on the joined result for the caller's own conditions.
 *
 * Putting 1)–3) together:
 *
 *     (SELECT h.*
 *        FROM ..._graph_hierarchyrelation AS h
 *        -- inner join -> only keep rows existing in the "left" and "right" table.
 *        INNER JOIN (
 *          -- find the highest content stream layer for the given hierarchy relation ID (in the given set of layers)
 *          SELECT id, MAX(contentstreamlayer) AS contentstreamlayer
 *            FROM ..._graph_hierarchyrelation
 *              WHERE (contentstreamlayer IN (:contentStreamLayers))
 *              -- NOTE: these additional where clauses are a performance optimization for the optimizer,
 *              --       so that it can check far fewer records.
 *              {$this->innerWhereClauses}
 *          GROUP BY id
 *        ) AS readHierarchy
 *          -- keep only the max content stream layer for each ID in the result set (INNER JOIN)
 *          ON h.id = readHierarchy.id AND h.contentstreamlayer = readHierarchy.contentstreamlayer
 *      -- restrict the hierarchy relation with whatever conditions we need.
 *      {$this->whereClauses})
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
 * @internal
 */
final readonly class HierarchyRelationStatement
{
    /**
     * @param array<string> $whereClauses applied to the outer (already layer-resolved) hierarchy relation `h`
     * @param array<string> $innerWhereClauses applied to the inner layer-resolution scan, i.e. to the candidate set
     *        whose highest layer wins (see CONCEPT in the class docblock). These MUST only ever reference the `id`
     *        column (see {@see andInnerWhereRelationIdMatches()}): a predicate filtering the inner rows directly on a
     *        nullable column (childnodeanchor, parentnodeanchor, dimensionspacepointhash) would exclude the removal
     *        tombstone, making the layer resolution skip the tombstone and resurrect the deleted node.
     */
    private function __construct(
        private ContentGraphTableNames $tableNames,
        private array $whereClauses,
        private array $innerWhereClauses,
    ) {
    }

    public static function for(ContentGraphTableNames $tableNames): self
    {
        return new self($tableNames, [], []);
    }

    /**
     * Replaces the outer where clauses with the given predicate (drops any previously set outer clauses).
     * Use {@see andWhere()} to append instead.
     */
    public function where(string $where): self
    {
        return new self(
            tableNames: $this->tableNames,
            whereClauses: $where === '' ? [] : [$where],
            innerWhereClauses: $this->innerWhereClauses,
        );
    }

    /**
     * Appends an outer where clause (applied to the already layer-resolved hierarchy relation `h`).
     */
    public function andWhere(string $where): self
    {
        return new self(
            tableNames: $this->tableNames,
            whereClauses: [...$this->whereClauses, ...($where === '' ? [] : [$where])],
            innerWhereClauses: $this->innerWhereClauses,
        );
    }

    /**
     * Adds a predicate applied to the inner layer-resolution scan (the candidate set whose highest layer wins).
     * Private on purpose: the only tombstone-safe inner predicate is one keyed on `id` (see the constructor docblock),
     * so all inner pushdowns must go through {@see andInnerWhereRelationIdMatches()}.
     */
    private function andInnerWhere(string $where): self
    {
        return new self(
            tableNames: $this->tableNames,
            whereClauses: $this->whereClauses,
            innerWhereClauses: [...$this->innerWhereClauses, ...($where === '' ? [] : [$where])],
        );
    }

    /**
     * Restricts the derived "max layer per id" subquery to the relation ids that satisfy the given predicate in *some*
     * layer, by pushing down `id IN (SELECT id FROM hierarchyrelation WHERE $relationPredicate)`. This is the only
     * tombstone-safe and move-safe way to push an outer filter into the subquery:
     *
     * - Tombstone-safe: filtering by `id` keeps *all* layers of every candidate relation in the layer resolution,
     *   including the removal tombstone (NULL anchors/dsp but same id and highest layer), so the winning layer stays
     *   exact and removed nodes correctly drop out. A direct predicate on a nullable column would exclude the tombstone
     *   and resurrect the node (see CONCEPT in the class docblock).
     * - Move-safe: a single anchor or a parent anchor is not layer-invariant for a relation id (copy-on-write reassigns
     *   the child anchor across layers, a move reassigns the parent); the id-based superset still keeps every layer of
     *   the matching relations, and the caller's outer WHERE trims the extras.
     *
     * This is purely a prefilter: it MAY keep more rows than the caller ultimately wants, but it must NEVER keep fewer.
     *
     * Selective because the filtered columns (childnodeanchor, parentnodeanchor, dimensionspacepointhash) are indexed.
     * $relationPredicate must reference unaliased relation columns, e.g. 'childnodeanchor = :x'.
     */
    public function andInnerWhereRelationIdMatches(string $relationPredicate): self
    {
        return $this->andInnerWhere(sprintf(
            'id IN (SELECT id FROM %s WHERE %s)',
            $this->tableNames->hierarchyRelation(),
            $relationPredicate
        ));
    }

    public function toSql(): string
    {
        $innerAdditionalWhereClauses = $this->innerWhereClauses === [] ? '' : sprintf("\n      AND %s", join("\n      AND ", $this->innerWhereClauses));
        $additionalWhereClauses = $this->whereClauses === [] ? '' : sprintf("\n  AND %s", join("\n  AND ", $this->whereClauses));

        return <<<SQL
            (SELECT h.*
              FROM {$this->tableNames->hierarchyRelation()} AS h
              WHERE (h.contentstreamlayer IN (:contentStreamLayers)){$innerAdditionalWhereClauses}
                AND NOT EXISTS (
                  SELECT 1
                    FROM {$this->tableNames->hierarchyRelation()} hWin
                    WHERE hWin.id = h.id
                      AND hWin.contentstreamlayer IN (:contentStreamLayers)
                      AND hWin.contentstreamlayer > h.contentstreamlayer
                ){$additionalWhereClauses}
            )
            SQL;
    }
}
