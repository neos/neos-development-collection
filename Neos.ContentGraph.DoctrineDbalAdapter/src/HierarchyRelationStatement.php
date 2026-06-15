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
     *        Only safe for predicates on columns that are layer-invariant for a given relation id (e.g. dimensionspacepointhash)
     *        or that match across all layers (e.g. childnodeanchor restricted to a node aggregate, since node rows are shared
     *        across layers). Pushing these down lets the optimizer use an index instead of grouping the whole relation table.
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
     * Adds a predicate applied INSIDE the derived "max layer per id" subquery (referencing the unaliased relation columns).
     * See the constructor docblock for the correctness constraints.
     */
    public function andInnerWhere(string $where): self
    {
        return new self(
            tableNames: $this->tableNames,
            whereClauses: $this->whereClauses,
            innerWhereClauses: [...$this->innerWhereClauses, ...($where === '' ? [] : [$where])],
        );
    }

    /**
     * Restricts the derived "max layer per id" subquery to the relation ids that satisfy the given anchor predicate in
     * *some* layer. This is the move-safe way to push down an outer single-anchor filter (e.g. `h.childnodeanchor = :x`
     * or `h.parentnodeanchor = :x`): a single anchor or a parent anchor is NOT layer-invariant for a relation id
     * (copy-on-write reassigns the child anchor across layers, a move reassigns the parent), so filtering the inner rows
     * directly by the anchor could drop the winning-layer row and elect a stale MAX(layer). Filtering by `id` instead
     * keeps *all* layers of every candidate relation, so MAX(layer) stays exact; the resulting superset is then trimmed
     * by the caller's outer WHERE on the same anchor. Selective because the anchor columns are indexed.
     *
     * $anchorPredicate must reference unaliased relation columns, e.g. 'childnodeanchor = :x'.
     */
    public function andInnerWhereRelationIdMatches(string $anchorPredicate): self
    {
        return $this->andInnerWhere(sprintf(
            'id IN (SELECT id FROM %s WHERE %s)',
            $this->tableNames->hierarchyRelation(),
            $anchorPredicate
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
