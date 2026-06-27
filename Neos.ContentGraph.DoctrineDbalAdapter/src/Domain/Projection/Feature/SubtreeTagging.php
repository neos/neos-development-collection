<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayers;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\SubtreeTagging\ChildHierarchyRelation;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\SubtreeTagging\HierarchyRelationRow;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\SubtreeTagging\HierarchyRelationRowWithoutLayer;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\SubtreeTagging\SubtreeTagPath;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature\SubtreeTagging\SubtreeTagSerializer;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoints;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentGraph\DoctrineDbalAdapter\NodeAggregateIdCondition;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Dbal\Query\StaticWhereCondition;

/**
 * The subtree tagging projection feature trait
 *
 * @internal
 */
trait SubtreeTagging
{
    private function addSubtreeTag(ContentStreamLayers $contentStreamLayers, NodeAggregateId $nodeAggregateId, DimensionSpacePointSet $affectedDimensionSpacePoints, SubtreeTag $subtreeTag): void
    {
        // Performance: walk the subtree ONE LEVEL AT A TIME (more performant than recursive CTEs, as we've learned).
        //
        // We walk per dimension space point to keep the parent<->child dsp pairing exact (a single parent anchor may
        // have children in several covered dimensions).
        try {
            foreach ($affectedDimensionSpacePoints as $dimensionSpacePoint) {
                $seed = $this->findWinningRelationOfNodeAggregate($contentStreamLayers, $nodeAggregateId, $dimensionSpacePoint);
                if ($seed === null) {
                    // the node aggregate is not present in this dimension - nothing to cascade into
                    continue;
                }
                $childNodeAnchors = NodeRelationAnchorPoints::create($seed->childNodeAnchor);
                while (($level = $this->findUntaggedWinningChildRelationsOfAnchors($contentStreamLayers, $childNodeAnchors, $dimensionSpacePoint, $subtreeTag)) !== []) {
                    $this->writeInheritedSubtreeTag($contentStreamLayers, $level, $subtreeTag);
                    $childNodeAnchors = NodeRelationAnchorPoints::create(...array_map(fn (ChildHierarchyRelation $child) => $child->childNodeAnchor, $level));
                }
            }
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('1: Failed to add subtree tag %s for content stream %s, node aggregate id %s and dimension space points %s: %s', $subtreeTag->value, $contentStreamLayers->toDebugString(), $nodeAggregateId->value, $affectedDimensionSpacePoints->toJson(), $e->getMessage()), 1716479749, $e);
        }

        $nodeAggregateIdCondition = NodeAggregateIdCondition::forNodeAggregateId($nodeAggregateId);
        $hierarchyStatement = $this->subqueries->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoints($affectedDimensionSpacePoints)->withPossibleChildNodeAggregateId($nodeAggregateIdCondition);
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
              {$nodeAggregateIdCondition->toWhereSql('n')}
            ON DUPLICATE KEY UPDATE subtreetags = VALUES(subtreetags)
            SQL;
        try {
            $this->dbal->executeStatement($addTagToNodeStatement, [
                'tagPath' => SubtreeTagPath::create($subtreeTag),
                'targetContentStreamLayer' => $contentStreamLayers->getWriteLayer()->value,
                ...$hierarchyStatement->getParameters()->toDbalValues(),
                ...$nodeAggregateIdCondition->getParameters()->toDbalValues(),
            ], [
                ...$hierarchyStatement->getParameters()->toDbalTypes(),
                ...$nodeAggregateIdCondition->getParameters()->toDbalTypes(),
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('2: Failed to add subtree tag %s for content stream %s, node aggregate id %s and dimension space points %s: %s', $subtreeTag->value, $contentStreamLayers->toDebugString(), $nodeAggregateId->value, $affectedDimensionSpacePoints->toJson(), $e->getMessage()), 1716479840, $e);
        }
    }

    /**
     * Resolve the untagged winning child hierarchy relations of a whole frontier of parent node anchors in one query,
     * scoped to a single dimension space point. This is the per-level batch step of the subtree walk.
     *
     * @return list<ChildHierarchyRelation>
     */
    private function findUntaggedWinningChildRelationsOfAnchors(ContentStreamLayers $contentStreamLayers, NodeRelationAnchorPoints $parentNodeAnchors, DimensionSpacePoint $dimensionSpacePoint, SubtreeTag $subtreeTag): array
    {
        if ($parentNodeAnchors->isEmpty()) {
            return [];
        }
        $hierarchyStatement = $this->subqueries->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoint($dimensionSpacePoint)
            ->withParentNodeRelationAnchors($parentNodeAnchors)
            ->withWhereCondition(StaticWhereCondition::fromString('h', "NOT JSON_CONTAINS_PATH(h.subtreetags, 'one', :tagPath)"));

        $statement = <<<SQL
            SELECT h.id, h.contentstreamlayer, h.childnodeanchor
            FROM {$hierarchyStatement->toSql()} h
            SQL;
        $rows = $this->dbal->fetchAllAssociative($statement, [
            'tagPath' => SubtreeTagPath::create($subtreeTag),
            ...$hierarchyStatement->getParameters()->toDbalValues(),
        ], [
            ...$hierarchyStatement->getParameters()->toDbalTypes(),
        ]);
        return array_map(
            ChildHierarchyRelation::fromArray(...),
            $rows
        );
    }

    /**
     * Write the inherited subtree tag (null marker) onto the given winning relations, copying each into the write layer.
     * The relations are addressed by their unique (id, contentstreamlayer) so no layer re-resolution is needed.
     *
     * @param list<ChildHierarchyRelation> $relations
     */
    private function writeInheritedSubtreeTag(ContentStreamLayers $contentStreamLayers, array $relations, SubtreeTag $subtreeTag): void
    {
        if ($relations === []) {
            return;
        }
        $idLayerPairs = implode(',', array_map(
            static fn (ChildHierarchyRelation $row) => '(' . $row->id->value . ',' . $row->contentStreamLayer->value . ')',
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
            'tagPath' => SubtreeTagPath::create($subtreeTag),
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
    private function removeSubtreeTag(ContentStreamLayers $contentStreamLayers, NodeAggregateId $nodeAggregateId, DimensionSpacePointSet $affectedDimensionSpacePoints, SubtreeTag $subtreeTag): void
    {
        $tagKey = $subtreeTag->value;
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
                $stillInherited = $this->relationContainsTag($contentStreamLayers, NodeRelationAnchorPoints::fromArray([$seed->parentNodeAnchor->value]), $dimensionSpacePoint, $subtreeTag);

                $toProcess = [$seed];
                $visited = [$seed->childNodeAnchor->value => true];
                while ($toProcess !== []) {
                    $writeBatch = [];
                    $frontierAnchors = [];
                    foreach ($toProcess as $relation) {
                        $currentTags = SubtreeTagSerializer::decodeSubtreeTags($relation->subtreeTags);
                        $newTags = $currentTags;
                        if ($stillInherited) {
                            // JSON_SET(subtreetags, :tagPath, null): explicit `true` becomes inherited `null`
                            $newTags[$tagKey] = null;
                        } else {
                            // JSON_REMOVE(subtreetags, :tagPath): drop the tag entirely
                            unset($newTags[$tagKey]);
                        }
                        if (!SubtreeTagSerializer::subtreeTagsEqual($newTags, $currentTags)) {
                            $writeBatch[] = new HierarchyRelationRowWithoutLayer(
                                id: $relation->id,
                                parentNodeAnchor: $relation->parentNodeAnchor,
                                childNodeAnchor: $relation->childNodeAnchor,
                                position: $relation->position,
                                dimensionSpacePointHash: $relation->dimensionSpacePointHash,
                                subtreeTags: SubtreeTagSerializer::encodeSubtreeTags($newTags),
                            );
                        }
                        $frontierAnchors[] = $relation->childNodeAnchor;
                    }
                    $this->writeRecomputedSubtreeTags($contentStreamLayers, $writeBatch);

                    // descend only into children that carry the tag as INHERITED (null); stop at explicit (true) or absent
                    $children = $this->findWinningChildRelationsOfAnchors($contentStreamLayers, NodeRelationAnchorPoints::create(...$frontierAnchors), $dimensionSpacePoint);
                    $toProcess = [];
                    foreach ($children as $child) {
                        $childAnchor = $child->childNodeAnchor->value;
                        if (isset($visited[$childAnchor])) {
                            continue;
                        }
                        $childTags = SubtreeTagSerializer::decodeSubtreeTags($child->subtreeTags);
                        if (!array_key_exists($tagKey, $childTags) || $childTags[$tagKey] !== null) {
                            continue;
                        }
                        $visited[$childAnchor] = true;
                        $toProcess[] = $child;
                    }
                }
            }
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to remove subtree tag %s for content stream %s, node aggregate id %s and dimension space points %s: %s', $subtreeTag->value, $contentStreamLayers->toDebugString(), $nodeAggregateId->value, $affectedDimensionSpacePoints->toJson(), $e->getMessage()), 1716482293, $e);
        }
    }

    /**
     * Whether any winning relation among the given node anchors carries the tag (explicit or inherited) in the given
     * dimension - used by {@see removeSubtreeTag()} to decide, per dimension, whether the node still inherits the tag
     * from its parent.
     *
     */
    private function relationContainsTag(ContentStreamLayers $contentStreamLayers, NodeRelationAnchorPoints $childNodeAnchors, DimensionSpacePoint $dimensionSpacePoint, SubtreeTag $subtreeTag): bool
    {
        if ($childNodeAnchors->isEmpty()) {
            return false;
        }
        $hierarchyStatement = $this->subqueries->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoint($dimensionSpacePoint)
            ->withChildNodeRelationAnchors($childNodeAnchors)
            ->withWhereCondition(StaticWhereCondition::fromString('h', "JSON_CONTAINS_PATH(h.subtreetags, 'one', :tagPath)"));

        $statement = <<<SQL
            SELECT 1
            FROM {$hierarchyStatement->toSql()} h
            LIMIT 1
            SQL;
        $result = $this->dbal->fetchOne($statement, [
            'tagPath' => SubtreeTagPath::create($subtreeTag),
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
                $seed->childNodeAnchor->value => array_keys(SubtreeTagSerializer::decodeSubtreeTags($seed->subtreeTags)),
            ];
            $visited = [$seed->childNodeAnchor->value => true];
            $frontier = [$seed->childNodeAnchor];

            while ($frontier !== []) {
                $children = $this->findWinningChildRelationsOfAnchors($contentStreamLayers, NodeRelationAnchorPoints::create(...$frontier), $coveredDimensionSpacePoint);
                $writeBatch = [];
                $nextFrontier = [];
                foreach ($children as $child) {
                    $childAnchor = $child->childNodeAnchor;
                    if (isset($visited[$childAnchor->value])) {
                        // cycle guard (a content tree must not contain cycles, but never loop forever on malformed data)
                        continue;
                    }
                    $parentAccumulatedSet = $accumulatedSetByAnchor[$child->parentNodeAnchor->value] ?? [];
                    $currentTags = SubtreeTagSerializer::decodeSubtreeTags($child->subtreeTags);

                    // {accumulated set => null} overlaid with {own explicit keys => true}
                    $newTags = array_fill_keys($parentAccumulatedSet, null);
                    foreach ($currentTags as $key => $value) {
                        if ($value === true) {
                            $newTags[$key] = true;
                        }
                    }
                    $accumulatedSetByAnchor[$childAnchor->value] = array_keys($newTags);

                    if (!SubtreeTagSerializer::subtreeTagsEqual($newTags, $currentTags)) {
                        $writeBatch[] = new HierarchyRelationRowWithoutLayer(
                            id: $child->id,
                            parentNodeAnchor: $child->parentNodeAnchor,
                            childNodeAnchor: $childAnchor,
                            position: $child->position,
                            dimensionSpacePointHash: $child->dimensionSpacePointHash,
                            subtreeTags: SubtreeTagSerializer::encodeSubtreeTags($newTags),
                        );
                    }
                    $visited[$childAnchor->value] = true;
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
     */
    private function findWinningRelationOfNodeAggregate(ContentStreamLayers $contentStreamLayers, NodeAggregateId $nodeAggregateId, DimensionSpacePoint $dimensionSpacePoint): ?HierarchyRelationRow
    {
        $nodeAggregateIdCondition = NodeAggregateIdCondition::forNodeAggregateId($nodeAggregateId);
        $hierarchyStatement = $this->subqueries->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoint($dimensionSpacePoint)->withPossibleChildNodeAggregateId($nodeAggregateIdCondition);
        $statement = <<<SQL
            SELECT h.id, h.contentstreamlayer, h.parentnodeanchor, h.childnodeanchor, h.position, h.dimensionspacepointhash, h.subtreetags
            FROM {$hierarchyStatement->toSql()} h
              INNER JOIN {$this->tableNames->node()} n ON n.relationanchorpoint = h.childnodeanchor
            WHERE
              {$nodeAggregateIdCondition->toWhereSql('n')}
            SQL;
        $row = $this->dbal->fetchAssociative($statement, [
            ...$hierarchyStatement->getParameters()->toDbalValues(),
            ...$nodeAggregateIdCondition->getParameters()->toDbalValues(),
        ], [
            ...$hierarchyStatement->getParameters()->toDbalTypes(),
            ...$nodeAggregateIdCondition->getParameters()->toDbalTypes(),
        ]);
        return $row === false ? null : HierarchyRelationRow::fromArray($row);
    }

    /**
     * Resolve the winning child hierarchy relations of a whole frontier of parent node anchors in one query, scoped to a
     * single dimension - the per-level batch step of the {@see moveSubtreeTags()} walk. Unlike the addSubtreeTag variant
     * this has no "untagged" filter (a move recomputes the WHOLE subtree) and returns every column needed to rebuild a
     * write-layer row.
     *
     * @return list<HierarchyRelationRow>
     */
    private function findWinningChildRelationsOfAnchors(ContentStreamLayers $contentStreamLayers, NodeRelationAnchorPoints $parentNodeAnchors, DimensionSpacePoint $dimensionSpacePoint): array
    {
        if ($parentNodeAnchors->isEmpty()) {
            return [];
        }

        $hierarchyStatement = $this->subqueries->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoint($dimensionSpacePoint)
            ->withParentNodeRelationAnchors($parentNodeAnchors);

        $statement = <<<SQL
            SELECT h.id, h.contentstreamlayer, h.parentnodeanchor, h.childnodeanchor, h.position, h.dimensionspacepointhash, h.subtreetags
            FROM {$hierarchyStatement->toSql()} h
            SQL;
        $rows = $this->dbal->fetchAllAssociative($statement, [
            ...$hierarchyStatement->getParameters()->toDbalValues(),
        ], [
            ...$hierarchyStatement->getParameters()->toDbalTypes(),
        ]);
        return array_map(
            HierarchyRelationRow::fromArray(...),
            $rows
        );
    }

    /**
     * Write the PHP-recomputed subtree tags onto the given relations, copying each into the write layer. Every column is
     * supplied from the already-fetched winning row, so no layer re-resolution is needed.
     *
     * @param list<HierarchyRelationRowWithoutLayer> $relations
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
            $parameters["id$i"] = $relation->id->value;
            $parameters["parentnodeanchor$i"] = $relation->parentNodeAnchor->value;
            $parameters["childnodeanchor$i"] = $relation->childNodeAnchor->value;
            $parameters["position$i"] = $relation->position;
            $parameters["subtreetags$i"] = $relation->subtreeTags;
            // Todo deduplicate same dimensionspacepointhash from payload
            $parameters["dimensionspacepointhash$i"] = $relation->dimensionSpacePointHash;
        }
        $values = implode(",\n", $rowPlaceholders);
        $statement = <<<SQL
            INSERT INTO {$this->tableNames->hierarchyRelation()} (
              id, parentnodeanchor, childnodeanchor, position, subtreetags, dimensionspacepointhash, contentstreamlayer
            )
            VALUES {$values}
            ON DUPLICATE KEY UPDATE subtreetags = VALUES(subtreetags)
            SQL;
        $this->dbal->executeStatement($statement, $parameters);
    }

    private function subtreeTagsForHierarchyRelation(ContentStreamLayers $contentStreamLayers, NodeRelationAnchorPoint $parentNodeAnchorPoint, DimensionSpacePoint $dimensionSpacePoint): NodeTags
    {
        if ($parentNodeAnchorPoint->equals(NodeRelationAnchorPoint::forRootEdge())) {
            return NodeTags::createEmpty();
        }

        $hierarchyStatement = $this->subqueries->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoint($dimensionSpacePoint)->withChildNodeRelationAnchor($parentNodeAnchorPoint);

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
