<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter;

/**
 * @internal
 */
final readonly class HierarchyRelationStatement
{
    /**
     * @param array<string> $whereClauses applied to the outer (already layer-resolved) hierarchy relation `h`
     * @param array<string> $innerWhereClauses applied inside the derived "max layer per id" subquery, BEFORE the GROUP BY.
     *        These MUST only ever reference the `id` column (see {@see andInnerWhereRelationIdMatches()}): node removal
     *        writes a tombstone row (same id, highest contentstreamlayer, every other column NULL) that has to *win* the
     *        MAX(contentstreamlayer) GROUP BY id resolution so the removed node drops out. A predicate filtering the inner
     *        rows directly on a nullable column (childnodeanchor, parentnodeanchor, dimensionspacepointhash) would exclude
     *        that tombstone, making MAX(layer) resolve to the pre-removal layer and resurrecting the deleted node.
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

    public function where(string $where): self
    {
        return new self(
            tableNames: $this->tableNames,
            whereClauses: $where === '' ? [] : [$where],
            innerWhereClauses: $this->innerWhereClauses,
        );
    }

    public function andWhere(string $where): self
    {
        return new self(
            tableNames: $this->tableNames,
            whereClauses: [...$this->whereClauses, ...($where === '' ? [] : [$where])],
            innerWhereClauses: $this->innerWhereClauses,
        );
    }

    /**
     * Adds a predicate applied INSIDE the derived "max layer per id" subquery, BEFORE the GROUP BY.
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
     * - Tombstone-safe: filtering by `id` keeps *all* layers of every candidate relation in the GROUP BY, including the
     *   removal tombstone (NULL anchors/dsp but same id and highest layer), so MAX(layer) stays exact and removed nodes
     *   correctly drop out. A direct predicate on a nullable column would exclude the tombstone and resurrect the node.
     * - Move-safe: a single anchor or a parent anchor is not layer-invariant for a relation id (copy-on-write reassigns
     *   the child anchor across layers, a move reassigns the parent); the id-based superset still keeps every layer of
     *   the matching relations, and the caller's outer WHERE trims the extras.
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
        $additionalWhereClauses = $this->whereClauses === [] ? '' : sprintf("  WHERE %s\n", join("\n  AND ", $this->whereClauses));
        $innerAdditionalWhereClauses = $this->innerWhereClauses === [] ? '' : sprintf("\n      AND %s", join("\n      AND ", $this->innerWhereClauses));

        return <<<SQL
            (SELECT h.*
              FROM {$this->tableNames->hierarchyRelation()} AS h
              INNER JOIN (
                SELECT id, MAX(contentstreamlayer) AS contentstreamlayer
                  FROM {$this->tableNames->hierarchyRelation()}
                    WHERE (contentstreamlayer IN (:contentStreamLayers)){$innerAdditionalWhereClauses}
                GROUP BY id
              ) AS readHierarchy
                ON h.id = readHierarchy.id AND h.contentstreamlayer = readHierarchy.contentstreamlayer
            {$additionalWhereClauses
            })
            SQL;
    }
}
