<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayers;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentGraph\DoctrineDbalAdapter\NodeAggregateIdClause;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * The subtree tagging projection feature trait
 *
 * @internal
 */
trait SubtreeTagging
{
    private function addSubtreeTag(ContentStreamLayers $contentStreamLayers, NodeAggregateId $nodeAggregateId, DimensionSpacePointSet $affectedDimensionSpacePoints, SubtreeTag $tag): void
    {
        // Performance: walk the subtree ONE LEVEL AT A TIME, driven from PHP, instead of in a single recursive CTE.
        // A recursive CTE cannot scope its per-level layer resolution to the current frontier (the recursive relation
        // may not appear inside the derived MAX(contentstreamlayer) subquery), so it ends up resolving the whole
        // content stream per level — O(table) regardless of how small the tagged subtree is. By holding the frontier
        // in PHP, each level runs the proven, index-scoped resolution (`parentnodeanchor IN (frontier)` pushed into the
        // {@see HierarchyRelationStatement} subquery), so the operation scales with the SUBTREE size, not the table.
        //
        // We walk per dimension space point to keep the parent<->child dsp pairing exact (a single parent anchor may
        // have children in several covered dimensions). Each level: resolve the untagged winning child relations of the
        // current frontier (one query), write the inherited tag onto them (one query), then descend.
        //
        // Tombstones stay correct for free: a removed relation's winning row is the NULL-parent tombstone, which never
        // matches the outer `parentnodeanchor IN (frontier)`, so removed subtrees drop out (the id-based inner pushdown
        // keeps every layer, including the tombstone, in the resolution).
        $tagPath = '$."' . $tag->value . '"';
        try {
            foreach ($affectedDimensionSpacePoints as $dimensionSpacePoint) {
                $level = $this->findUntaggedWinningChildRelationsOfNodeAggregate($contentStreamLayers, $nodeAggregateId, $dimensionSpacePoint, $tagPath);
                while ($level !== []) {
                    $this->writeInheritedSubtreeTag($contentStreamLayers, $level, $tagPath);
                    $childNodeAnchors = array_map(static fn (array $row) => (int)$row['childnodeanchor'], $level);
                    $level = $this->findUntaggedWinningChildRelationsOfAnchors($contentStreamLayers, $childNodeAnchors, $dimensionSpacePoint, $tagPath);
                }
            }
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('1: Failed to add subtree tag %s for content stream %s, node aggregate id %s and dimension space points %s: %s', $tag->value, $contentStreamLayers->toDebugString(), $nodeAggregateId->value, $affectedDimensionSpacePoints->toJson(), $e->getMessage()), 1716479749, $e);
        }

        $nodeAggregateIdClause = NodeAggregateIdClause::forNodeAggregateId($nodeAggregateId);
        $hierarchyStatement = $this->statements->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoints($affectedDimensionSpacePoints)->withChildNodeAggregateIdPrefilter($nodeAggregateIdClause);
        $addTagToNodeStatement = <<<SQL
            INSERT INTO {$this->tableNames->hierarchyRelation()} (
              id,
              parentnodeanchor,
              childnodeanchor,
              position,
              subtreetags,
              dimensionspacepointhash,
              contentstreamlayer
            )
            SELECT
              h.id,
              h.parentnodeanchor,
              h.childnodeanchor,
              h.position,
              JSON_SET(h.subtreetags, :tagPath, true) as subtreetags,
              h.dimensionspacepointhash,
              :targetContentStreamLayer as contentstreamlayer
            FROM
              {$hierarchyStatement->toSql()} h
              INNER JOIN {$this->tableNames->node()} n ON n.relationanchorpoint = h.childnodeanchor
            WHERE
              {$nodeAggregateIdClause->toWhereSql()}
            ON DUPLICATE KEY UPDATE subtreetags = VALUES(subtreetags)
            SQL;
        try {
            $this->dbal->executeStatement($addTagToNodeStatement, [
                'tagPath' => $tagPath,
                'targetContentStreamLayer' => $contentStreamLayers->getWriteLayer()->value,
                ...$hierarchyStatement->getParameters()->toDbalValues(),
                ...$nodeAggregateIdClause->getParameters()->toDbalValues(),
            ], [
                ...$hierarchyStatement->getParameters()->toDbalTypes(),
                ...$nodeAggregateIdClause->getParameters()->toDbalTypes(),
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('2: Failed to add subtree tag %s for content stream %s, node aggregate id %s and dimension space points %s: %s', $tag->value, $contentStreamLayers->toDebugString(), $nodeAggregateId->value, $affectedDimensionSpacePoints->toJson(), $e->getMessage()), 1716479840, $e);
        }
    }

    /**
     * Resolve the untagged winning child hierarchy relations directly beneath the given node aggregate (the seed level
     * of the subtree walk), scoped to a single dimension space point. Index-driven via the new-parent aggregate's
     * relation anchors; tombstone-safe via the id-based inner pushdown.
     *
     * @return list<array{id:int, contentstreamlayer:int, childnodeanchor:int}>
     */
    private function findUntaggedWinningChildRelationsOfNodeAggregate(ContentStreamLayers $contentStreamLayers, NodeAggregateId $parentNodeAggregateId, DimensionSpacePoint $dimensionSpacePoint, string $tagPath): array
    {
        $nodeAggregateIdClause = NodeAggregateIdClause::forNodeAggregateId($parentNodeAggregateId);
        $hierarchyStatement = $this->statements->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoint($dimensionSpacePoint)->withParentNodeAggregateIdPrefilter($nodeAggregateIdClause)
            ->andWhere("NOT JSON_CONTAINS_PATH(h.subtreetags, 'one', :tagPath)");

        $statement = <<<SQL
            SELECT h.id, h.contentstreamlayer, h.childnodeanchor
            FROM {$hierarchyStatement->toSql()} h
              INNER JOIN {$this->tableNames->node()} n ON n.relationanchorpoint = h.parentnodeanchor
            WHERE
              {$nodeAggregateIdClause->toWhereSql()}
            SQL;
        /** @var list<array{id:int, contentstreamlayer:int, childnodeanchor:int}> $rows */
        $rows = $this->dbal->fetchAllAssociative($statement, [
            'tagPath' => $tagPath,
            ...$hierarchyStatement->getParameters()->toDbalValues(),
            ...$nodeAggregateIdClause->getParameters()->toDbalValues(),
        ], [
            ...$hierarchyStatement->getParameters()->toDbalTypes(),
            ...$nodeAggregateIdClause->getParameters()->toDbalTypes(),
        ]);
        return $rows;
    }

    /**
     * Resolve the untagged winning child hierarchy relations of a whole frontier of parent node anchors in one query,
     * scoped to a single dimension space point. This is the per-level batch step of the subtree walk.
     *
     * @param list<int> $parentNodeAnchors
     * @return list<array{id:int, contentstreamlayer:int, childnodeanchor:int}>
     */
    private function findUntaggedWinningChildRelationsOfAnchors(ContentStreamLayers $contentStreamLayers, array $parentNodeAnchors, DimensionSpacePoint $dimensionSpacePoint, string $tagPath): array
    {
        if ($parentNodeAnchors === []) {
            return [];
        }
        $hierarchyStatement = $this->statements->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoint($dimensionSpacePoint)
            ->andWhere('h.parentnodeanchor IN (:parentNodeAnchors)')
            ->andWhere("NOT JSON_CONTAINS_PATH(h.subtreetags, 'one', :tagPath)")
            ->andInnerWhereRelationIdMatches('parentnodeanchor IN (:parentNodeAnchors)');

        $statement = <<<SQL
            SELECT h.id, h.contentstreamlayer, h.childnodeanchor
            FROM {$hierarchyStatement->toSql()} h
            SQL;
        /** @var list<array{id:int, contentstreamlayer:int, childnodeanchor:int}> $rows */
        $rows = $this->dbal->fetchAllAssociative($statement, [
            'parentNodeAnchors' => $parentNodeAnchors,
            'tagPath' => $tagPath,
            ...$hierarchyStatement->getParameters()->toDbalValues(),
        ], [
            'parentNodeAnchors' => ArrayParameterType::INTEGER,
            ...$hierarchyStatement->getParameters()->toDbalTypes(),
        ]);
        return $rows;
    }

    /**
     * Write the inherited subtree tag (null marker) onto the given winning relations, copying each into the write layer.
     * The relations are addressed by their unique (id, contentstreamlayer) so no layer re-resolution is needed.
     *
     * @param list<array{id:int, contentstreamlayer:int, childnodeanchor:int}> $relations
     */
    private function writeInheritedSubtreeTag(ContentStreamLayers $contentStreamLayers, array $relations, string $tagPath): void
    {
        if ($relations === []) {
            return;
        }
        $idLayerPairs = implode(',', array_map(
            static fn (array $row) => '(' . (int)$row['id'] . ',' . (int)$row['contentstreamlayer'] . ')',
            $relations
        ));
        $statement = <<<SQL
            INSERT INTO {$this->tableNames->hierarchyRelation()} (
              id, parentnodeanchor, childnodeanchor, position, subtreetags, dimensionspacepointhash, contentstreamlayer
            )
            SELECT
              h.id,
              h.parentnodeanchor,
              h.childnodeanchor,
              h.position,
              JSON_INSERT(h.subtreetags, :tagPath, null) as subtreetags,
              h.dimensionspacepointhash,
              :targetContentStreamLayer as contentstreamlayer
            FROM {$this->tableNames->hierarchyRelation()} h
            WHERE (h.id, h.contentstreamlayer) IN ($idLayerPairs)
            ON DUPLICATE KEY UPDATE subtreetags = VALUES(subtreetags)
            SQL;
        $this->dbal->executeStatement($statement, [
            'tagPath' => $tagPath,
            'targetContentStreamLayer' => $contentStreamLayers->getWriteLayer()->value,
        ]);
    }

    /**
     * Remove an explicit subtree tag from a node aggregate, ONE LEVEL AT A TIME driven from PHP - the same scoped
     * per-level walk as {@see addSubtreeTag()}, run in reverse.
     *
     * Untagging the node has one of two effects, decided PER DIMENSION by whether the node still inherits the tag from
     * an ancestor (i.e. its parent relation in that dimension still carries the tag) - a node's parent, and thus its
     * inheritance, can differ per dimension:
     *  - still inherited -> the explicit `true` becomes an inherited `null` marker on the node; its descendants already
     *    carry the inherited `null` and stay unchanged.
     *  - no longer inherited -> the tag key is removed entirely from the node AND from every descendant that only
     *    inherited it.
     *
     * The walk starts at the node itself and descends only into children that carry the tag as INHERITED (`null`): a
     * child that has the tag set explicitly (`true`) keeps its own tag and its independently-tagged subtree, so we stop
     * there; a child without the tag at all is unaffected, so we stop there too. (This mirrors the recursive CTE's
     * `JSON_EXTRACT(subtreetags, :tagPath) != TRUE` frontier filter exactly.)
     *
     * Walked per dimension to keep the parent<->child dsp pairing exact. Tombstone-safe via the id-based inner pushdown.
     */
    private function removeSubtreeTag(ContentStreamLayers $contentStreamLayers, NodeAggregateId $nodeAggregateId, DimensionSpacePointSet $affectedDimensionSpacePoints, SubtreeTag $tag): void
    {
        $tagKey = $tag->value;
        $tagPath = '$."' . $tag->value . '"';
        try {
            foreach ($affectedDimensionSpacePoints as $dimensionSpacePoint) {
                $seed = $this->findWinningRelationOfNodeAggregate($contentStreamLayers, $nodeAggregateId, $dimensionSpacePoint);
                if ($seed === null) {
                    continue;
                }
                // Decide PER DIMENSION whether the node still inherits the tag from an ancestor: a node's parent -
                // and therefore whether it keeps the tag as an inherited `null` or loses it entirely - can differ per
                // dimension. We check the node's parent relation IN THIS DIMENSION (the seed's parentnodeanchor is the
                // parent's anchor in this dimension; the parent's incoming relation has childnodeanchor = that anchor).
                $stillInherited = $this->anyRelationContainsTag($contentStreamLayers, [(int)$seed['parentnodeanchor']], $dimensionSpacePoint, $tagPath);

                $toProcess = [$seed];
                $visited = [(int)$seed['childnodeanchor'] => true];
                while ($toProcess !== []) {
                    $writeBatch = [];
                    $frontierAnchors = [];
                    foreach ($toProcess as $relation) {
                        $currentTags = $this->decodeSubtreeTags($relation['subtreetags']);
                        $newTags = $currentTags;
                        if ($stillInherited) {
                            // JSON_SET(subtreetags, :tagPath, null): explicit `true` becomes inherited `null`
                            $newTags[$tagKey] = null;
                        } else {
                            // JSON_REMOVE(subtreetags, :tagPath): drop the tag entirely
                            unset($newTags[$tagKey]);
                        }
                        if (!$this->subtreeTagsEqual($newTags, $currentTags)) {
                            $writeBatch[] = [
                                'id' => (int)$relation['id'],
                                'parentnodeanchor' => (int)$relation['parentnodeanchor'],
                                'childnodeanchor' => (int)$relation['childnodeanchor'],
                                'position' => (int)$relation['position'],
                                'dimensionspacepointhash' => $relation['dimensionspacepointhash'],
                                'subtreetags' => $this->encodeSubtreeTags($newTags),
                            ];
                        }
                        $frontierAnchors[] = (int)$relation['childnodeanchor'];
                    }
                    $this->writeRecomputedSubtreeTags($contentStreamLayers, $writeBatch);

                    // descend only into children that carry the tag as INHERITED (null); stop at explicit (true) or absent
                    $children = $this->findWinningChildRelationsOfAnchors($contentStreamLayers, $frontierAnchors, $dimensionSpacePoint);
                    $toProcess = [];
                    foreach ($children as $child) {
                        $childAnchor = (int)$child['childnodeanchor'];
                        if (isset($visited[$childAnchor])) {
                            continue;
                        }
                        $childTags = $this->decodeSubtreeTags($child['subtreetags']);
                        if (!array_key_exists($tagKey, $childTags) || $childTags[$tagKey] !== null) {
                            continue;
                        }
                        $visited[$childAnchor] = true;
                        $toProcess[] = $child;
                    }
                }
            }
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to remove subtree tag %s for content stream %s, node aggregate id %s and dimension space points %s: %s', $tag->value, $contentStreamLayers->toDebugString(), $nodeAggregateId->value, $affectedDimensionSpacePoints->toJson(), $e->getMessage()), 1716482293, $e);
        }
    }

    /**
     * Whether any winning relation among the given node anchors carries the tag (explicit or inherited) in the given
     * dimension - used by {@see removeSubtreeTag()} to decide, per dimension, whether the node still inherits the tag
     * from its parent.
     *
     * @param list<int> $childNodeAnchors
     */
    private function anyRelationContainsTag(ContentStreamLayers $contentStreamLayers, array $childNodeAnchors, DimensionSpacePoint $dimensionSpacePoint, string $tagPath): bool
    {
        if ($childNodeAnchors === []) {
            return false;
        }
        $hierarchyStatement = $this->statements->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoint($dimensionSpacePoint)
            ->withChildNodeRelationAnchors($childNodeAnchors)
            ->andWhere("JSON_CONTAINS_PATH(h.subtreetags, 'one', :tagPath)");

        $statement = <<<SQL
            SELECT 1
            FROM {$hierarchyStatement->toSql()} h
            LIMIT 1
            SQL;
        $result = $this->dbal->fetchOne($statement, [
            'tagPath' => $tagPath,
            ...$hierarchyStatement->getParameters()->toDbalValues(),
        ], [
            ...$hierarchyStatement->getParameters()->toDbalTypes(),
        ]);
        return $result !== false;
    }

    /**
     * Recompute the full inherited-tag closure of the subtree rooted at the (just moved) new parent, ONE LEVEL AT A TIME
     * driven from PHP - the same scoped per-level walk as {@see addSubtreeTag()}, but a *set* recompute rather than an
     * additive one: a moved subtree must gain the tags inherited from its new ancestors AND drop the tags inherited from
     * its old ones.
     *
     * Per node we carry an accumulated set of tag keys from the new parent down to that node. The seed set is ALL keys of
     * the new parent's own subtree tags (its explicit tags plus the tags it itself inherits - its complete effective set
     * cascades down). Each descendant contributes only its OWN EXPLICIT (`true`) keys to the accumulator. A node's new
     * subtree tags are then {accumulated set => null} overlaid with {own explicit keys => true} - replicating the SQL's
     * `JSON_MERGE_PATCH(JSON_OBJECTAGG(inherited, null), JSON_MERGE_PATCH('{}', own))` exactly (own explicit overrides the
     * inherited null marker; stale inherited nulls not in the new accumulated set are dropped).
     *
     * Called once per covered dimension (see {@see \Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\NodeMove}),
     * so there is no dimension loop here. Tombstones stay correct for free via the id-based inner pushdown: a removed
     * relation's winning row is the NULL-parent tombstone, which never matches the outer `parentnodeanchor IN (frontier)`.
     */
    private function moveSubtreeTags(ContentStreamLayers $contentStreamLayers, NodeAggregateId $newParentNodeAggregateId, DimensionSpacePoint $coveredDimensionSpacePoint): void
    {
        try {
            $seed = $this->findWinningRelationOfNodeAggregate($contentStreamLayers, $newParentNodeAggregateId, $coveredDimensionSpacePoint);
            if ($seed === null) {
                // the new parent is not present in this dimension - nothing to recompute
                return;
            }
            // accumulated inherited tag-key set per (child)node anchor; seeded with ALL of the new parent's keys
            $accumulatedSetByAnchor = [
                (int)$seed['childnodeanchor'] => array_keys($this->decodeSubtreeTags($seed['subtreetags'])),
            ];
            $visited = [(int)$seed['childnodeanchor'] => true];
            $frontier = [(int)$seed['childnodeanchor']];

            while ($frontier !== []) {
                $children = $this->findWinningChildRelationsOfAnchors($contentStreamLayers, $frontier, $coveredDimensionSpacePoint);
                $writeBatch = [];
                $nextFrontier = [];
                foreach ($children as $child) {
                    $childAnchor = (int)$child['childnodeanchor'];
                    if (isset($visited[$childAnchor])) {
                        // cycle guard (a content tree must not contain cycles, but never loop forever on malformed data)
                        continue;
                    }
                    $parentAccumulatedSet = $accumulatedSetByAnchor[(int)$child['parentnodeanchor']] ?? [];
                    $currentTags = $this->decodeSubtreeTags($child['subtreetags']);

                    // {accumulated set => null} overlaid with {own explicit keys => true}
                    $newTags = array_fill_keys($parentAccumulatedSet, null);
                    foreach ($currentTags as $key => $value) {
                        if ($value === true) {
                            $newTags[$key] = true;
                        }
                    }
                    $accumulatedSetByAnchor[$childAnchor] = array_keys($newTags);

                    if (!$this->subtreeTagsEqual($newTags, $currentTags)) {
                        $writeBatch[] = [
                            'id' => (int)$child['id'],
                            'parentnodeanchor' => (int)$child['parentnodeanchor'],
                            'childnodeanchor' => $childAnchor,
                            'position' => (int)$child['position'],
                            'dimensionspacepointhash' => $child['dimensionspacepointhash'],
                            'subtreetags' => $this->encodeSubtreeTags($newTags),
                        ];
                    }
                    $visited[$childAnchor] = true;
                    $nextFrontier[] = $childAnchor;
                }
                $this->writeRecomputedSubtreeTags($contentStreamLayers, $writeBatch);
                $frontier = $nextFrontier;
            }
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to move subtree tags for content stream %s, new parent node aggregate id %s and dimension space point %s: %s', $contentStreamLayers->toDebugString(), $newParentNodeAggregateId->value, $coveredDimensionSpacePoint->toJson(), $e->getMessage()), 1716482574, $e);
        }
    }

    /**
     * Resolve the winning incoming hierarchy relation of a single node aggregate in one dimension - the seed of the
     * {@see moveSubtreeTags()} and {@see removeSubtreeTag()} walks. Returns every column needed to rebuild a write-layer
     * row. Index-driven and tombstone-safe via the id-based inner pushdown.
     *
     * @return array{id:int, contentstreamlayer:int, parentnodeanchor:int, childnodeanchor:int, position:int, dimensionspacepointhash:string, subtreetags:?string}|null
     */
    private function findWinningRelationOfNodeAggregate(ContentStreamLayers $contentStreamLayers, NodeAggregateId $nodeAggregateId, DimensionSpacePoint $dimensionSpacePoint): ?array
    {
        $nodeAggregateIdClause = NodeAggregateIdClause::forNodeAggregateId($nodeAggregateId);
        $hierarchyStatement = $this->statements->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoint($dimensionSpacePoint)->withChildNodeAggregateIdPrefilter($nodeAggregateIdClause);
        $statement = <<<SQL
            SELECT h.id, h.contentstreamlayer, h.parentnodeanchor, h.childnodeanchor, h.position, h.dimensionspacepointhash, h.subtreetags
            FROM {$hierarchyStatement->toSql()} h
              INNER JOIN {$this->tableNames->node()} n ON n.relationanchorpoint = h.childnodeanchor
            WHERE
              {$nodeAggregateIdClause->toWhereSql()}
            SQL;
        /** @var array{id:int, contentstreamlayer:int, parentnodeanchor:int, childnodeanchor:int, position:int, dimensionspacepointhash:string, subtreetags:?string}|false $row */
        $row = $this->dbal->fetchAssociative($statement, [
            ...$hierarchyStatement->getParameters()->toDbalValues(),
            ...$nodeAggregateIdClause->getParameters()->toDbalValues(),
        ], [
            ...$hierarchyStatement->getParameters()->toDbalTypes(),
            ...$nodeAggregateIdClause->getParameters()->toDbalTypes(),
        ]);
        return $row === false ? null : $row;
    }

    /**
     * Resolve the winning child hierarchy relations of a whole frontier of parent node anchors in one query, scoped to a
     * single dimension - the per-level batch step of the {@see moveSubtreeTags()} walk. Unlike the addSubtreeTag variant
     * this has no "untagged" filter (a move recomputes the WHOLE subtree) and returns every column needed to rebuild a
     * write-layer row.
     *
     * @param list<int> $parentNodeAnchors
     * @return list<array{id:int, contentstreamlayer:int, parentnodeanchor:int, childnodeanchor:int, position:int, dimensionspacepointhash:string, subtreetags:?string}>
     */
    private function findWinningChildRelationsOfAnchors(ContentStreamLayers $contentStreamLayers, array $parentNodeAnchors, DimensionSpacePoint $dimensionSpacePoint): array
    {
        if ($parentNodeAnchors === []) {
            return [];
        }

        $hierarchyStatement = $this->statements->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoint($dimensionSpacePoint)
            ->withParentNodeRelationAnchors($parentNodeAnchors);

        $statement = <<<SQL
            SELECT h.id, h.contentstreamlayer, h.parentnodeanchor, h.childnodeanchor, h.position, h.dimensionspacepointhash, h.subtreetags
            FROM {$hierarchyStatement->toSql()} h
            SQL;
        /** @var list<array{id:int, contentstreamlayer:int, parentnodeanchor:int, childnodeanchor:int, position:int, dimensionspacepointhash:string, subtreetags:?string}> $rows */
        $rows = $this->dbal->fetchAllAssociative($statement, [
            ...$hierarchyStatement->getParameters()->toDbalValues(),
        ], [
            ...$hierarchyStatement->getParameters()->toDbalTypes(),
        ]);
        return $rows;
    }

    /**
     * Write the PHP-recomputed subtree tags onto the given relations, copying each into the write layer. Every column is
     * supplied from the already-fetched winning row, so no layer re-resolution is needed.
     *
     * @param list<array{id:int, parentnodeanchor:int, childnodeanchor:int, position:int, dimensionspacepointhash:string, subtreetags:string}> $relations
     */
    private function writeRecomputedSubtreeTags(ContentStreamLayers $contentStreamLayers, array $relations): void
    {
        if ($relations === []) {
            return;
        }
        $rowPlaceholders = [];
        $parameters = ['targetContentStreamLayer' => $contentStreamLayers->getWriteLayer()->value];
        foreach ($relations as $i => $relation) {
            $rowPlaceholders[] = "(:id$i, :parentnodeanchor$i, :childnodeanchor$i, :position$i, :subtreetags$i, :dimensionspacepointhash$i, :targetContentStreamLayer)";
            $parameters["id$i"] = $relation['id'];
            $parameters["parentnodeanchor$i"] = $relation['parentnodeanchor'];
            $parameters["childnodeanchor$i"] = $relation['childnodeanchor'];
            $parameters["position$i"] = $relation['position'];
            $parameters["subtreetags$i"] = $relation['subtreetags'];
            $parameters["dimensionspacepointhash$i"] = $relation['dimensionspacepointhash'];
        }
        $values = implode(",\n", $rowPlaceholders);
        $statement = <<<SQL
            INSERT INTO {$this->tableNames->hierarchyRelation()} (
              id, parentnodeanchor, childnodeanchor, position, subtreetags, dimensionspacepointhash, contentstreamlayer
            )
            VALUES $values
            ON DUPLICATE KEY UPDATE subtreetags = VALUES(subtreetags)
            SQL;
        $this->dbal->executeStatement($statement, $parameters);
    }

    /**
     * Decode a subtree tags JSON column into an associative array of tag key => (true for explicit, null for inherited).
     *
     * @return array<string, true|null>
     */
    private function decodeSubtreeTags(?string $json): array
    {
        if ($json === null || $json === '' || $json === '{}' || $json === '[]') {
            return [];
        }
        /** @var array<string, true|null> $decoded */
        $decoded = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        return $decoded;
    }

    /**
     * Encode a recomputed tag set back to its JSON column representation, forcing an object so an emptied set becomes
     * `{}` rather than `[]`.
     *
     * @param array<string, true|null> $tags
     */
    private function encodeSubtreeTags(array $tags): string
    {
        if ($tags === []) {
            return '{}';
        }
        return json_encode($tags, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT);
    }

    /**
     * Order-independent comparison of two decoded subtree tag sets (so an unchanged relation is skipped regardless of
     * key order in the stored JSON).
     *
     * @param array<string, true|null> $a
     * @param array<string, true|null> $b
     */
    private function subtreeTagsEqual(array $a, array $b): bool
    {
        if (count($a) !== count($b)) {
            return false;
        }
        ksort($a);
        ksort($b);
        return $a === $b;
    }

    private function subtreeTagsForHierarchyRelation(ContentStreamLayers $contentStreamLayers, NodeRelationAnchorPoint $parentNodeAnchorPoint, DimensionSpacePoint $dimensionSpacePoint): NodeTags
    {
        if ($parentNodeAnchorPoint->equals(NodeRelationAnchorPoint::forRootEdge())) {
            return NodeTags::createEmpty();
        }

        $hierarchyStatement = $this->statements->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoint($dimensionSpacePoint)->withChildNodeRelationAnchor($parentNodeAnchorPoint);

        $subtreeTagsStatement = <<<SQL
            SELECT h.subtreetags FROM {$hierarchyStatement->toSql()} h
            SQL;

        try {
            $subtreeTagsJson = $this->dbal->fetchOne($subtreeTagsStatement, [
                ...$hierarchyStatement->getParameters()->toDbalValues(),
            ], [
                ...$hierarchyStatement->getParameters()->toDbalTypes(),
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch subtree tags for hierarchy parent anchor point "%s" in content subgraph "%s@%s": %s', $parentNodeAnchorPoint->value, $dimensionSpacePoint->toJson(), $contentStreamLayers->toDebugString(), $e->getMessage()), 1716478760, $e);
        }
        if (!is_string($subtreeTagsJson)) {
            throw new \RuntimeException(sprintf('Failed to fetch subtree tags for hierarchy parent anchor point "%s" in content subgraph "%s@%s"', $parentNodeAnchorPoint->value, $dimensionSpacePoint->toJson(), $contentStreamLayers->toDebugString()), 1704199847);
        }
        return NodeFactory::extractNodeTagsFromJson($subtreeTagsJson);
    }
}
