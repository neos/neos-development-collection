<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamLayers;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
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
        $allHierarchyStatement = $this->statements->forHierarchyRelation($contentStreamLayers);
        $hierarchyStatementNested = $this->statements->forHierarchyRelation($contentStreamLayers)
            ->withDimensionSpacePoints($affectedDimensionSpacePoints)
            ->where("NOT JSON_CONTAINS_PATH(h.subtreetags, 'one', :tagPath)");

        $addTagToDescendantsStatement = <<<SQL
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
              JSON_INSERT(h.subtreetags, :tagPath, null) as subtreetags,
              h.dimensionspacepointhash,
              :targetContentStreamLayer as contentstreamlayer
            FROM
              {$allHierarchyStatement->toSql()} h
              JOIN (
                WITH
                  RECURSIVE cte (childnodeanchor, dsp) AS (
                    SELECT
                      ch.childnodeanchor,
                      ch.dimensionspacepointhash
                    FROM
                      {$hierarchyStatementNested->toSql()} ch
                      INNER JOIN {$this->tableNames->node()} n ON n.relationanchorpoint = ch.parentnodeanchor
                    WHERE
                      n.nodeaggregateid = :nodeAggregateId
                    UNION ALL
                    SELECT
                      dh.childnodeanchor,
                      dh.dimensionspacepointhash
                    FROM
                      cte
                      JOIN {$allHierarchyStatement->toSql()} dh ON dh.parentnodeanchor = cte.childnodeanchor
                      AND dh.dimensionspacepointhash = cte.dsp
                    WHERE
                      NOT JSON_CONTAINS_PATH(dh.subtreetags, 'one', :tagPath)
                  )
                SELECT
                  *
                FROM
                  cte
              ) subquery ON h.dimensionspacepointhash = subquery.dsp
              AND h.childnodeanchor = subquery.childnodeanchor
            ON DUPLICATE KEY UPDATE subtreetags = VALUES(subtreetags)
            SQL;

        try {
            $this->dbal->executeStatement($addTagToDescendantsStatement, [
                'nodeAggregateId' => $nodeAggregateId->value,
                'tagPath' => '$."' . $tag->value . '"',
                'targetContentStreamLayer' => $contentStreamLayers->getWriteLayer()->value,
                ...$allHierarchyStatement->getParameters()->toDbalValues(),
                ...$hierarchyStatementNested->getParameters()->toDbalValues(),
            ], [
                ...$allHierarchyStatement->getParameters()->toDbalTypes(),
                ...$hierarchyStatementNested->getParameters()->toDbalTypes(),
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('1: Failed to add subtree tag %s for content stream %s, node aggregate id %s and dimension space points %s: %s', $tag->value, $contentStreamLayers->toDebugString(), $nodeAggregateId->value, $affectedDimensionSpacePoints->toJson(), $e->getMessage()), 1716479749, $e);
        }

        $hierarchyStatement = $this->statements->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoints($affectedDimensionSpacePoints);
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
              n.nodeaggregateid = :nodeAggregateId
            ON DUPLICATE KEY UPDATE subtreetags = VALUES(subtreetags)
            SQL;
        try {
            $this->dbal->executeStatement($addTagToNodeStatement, [
                'nodeAggregateId' => $nodeAggregateId->value,
                'tagPath' => '$."' . $tag->value . '"',
                'targetContentStreamLayer' => $contentStreamLayers->getWriteLayer()->value,
                ...$hierarchyStatement->getParameters()->toDbalValues(),
            ], [
                ...$hierarchyStatement->getParameters()->toDbalTypes(),
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('2: Failed to add subtree tag %s for content stream %s, node aggregate id %s and dimension space points %s: %s', $tag->value, $contentStreamLayers->toDebugString(), $nodeAggregateId->value, $affectedDimensionSpacePoints->toJson(), $e->getMessage()), 1716479840, $e);
        }
    }

    private function removeSubtreeTag(ContentStreamLayers $contentStreamLayers, NodeAggregateId $nodeAggregateId, DimensionSpacePointSet $affectedDimensionSpacePoints, SubtreeTag $tag): void
    {
        $allHierarchyStatement = $this->statements->forHierarchyRelation($contentStreamLayers);
        $nestedHierarchyStatement = $this->statements->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoints($affectedDimensionSpacePoints);
        $removeTagStatement = <<<SQL
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
              h2.id,
              h2.parentnodeanchor,
              h2.childnodeanchor,
              h2.position,
              h2.subtreetags,
              h2.dimensionspacepointhash,
              h2.contentstreamlayer
            FROM
              (
                SELECT
                  h.id,
                  h.parentnodeanchor,
                  h.childnodeanchor,
                  h.position,
                  IF(
                    (
                      SELECT
                        containsTag
                      FROM
                        (
                          SELECT
                            JSON_CONTAINS_PATH(gph.subtreetags, 'one', :tagPath) as containsTag
                          FROM
                            {$allHierarchyStatement->toSql()} gph
                            INNER JOIN {$allHierarchyStatement->toSql()} ph ON ph.parentnodeanchor = gph.childnodeanchor
                            INNER JOIN {$this->tableNames->node()} n ON n.relationanchorpoint = ph.childnodeanchor
                          WHERE
                            ph.parentnodeanchor = gph.childnodeanchor
                            AND n.nodeaggregateid = :nodeAggregateId
                          LIMIT
                            1
                        ) as containsTagSubQuery
                    ),
                    JSON_SET(subtreetags, :tagPath, null),
                    JSON_REMOVE(subtreetags, :tagPath)
                  ) as subtreetags,
                  h.subtreetags as currentsubtreetags,
                  h.dimensionspacepointhash,
                  :targetContentStreamLayer as contentstreamlayer
                FROM
                  {$allHierarchyStatement->toSql()} h
                  JOIN (
                    WITH
                      RECURSIVE cte (childnodeanchor, dsp) AS (
                        SELECT
                          ph.childnodeanchor,
                          ph.dimensionspacepointhash
                        FROM
                          {$nestedHierarchyStatement->toSql()} ph
                          INNER JOIN {$this->tableNames->node()} n ON n.relationanchorpoint = ph.childnodeanchor
                        WHERE
                          n.nodeaggregateid = :nodeAggregateId
                        UNION ALL
                        SELECT
                          dh.childnodeanchor,
                          dh.dimensionspacepointhash
                        FROM
                          cte
                          JOIN {$allHierarchyStatement->toSql()} dh ON dh.parentnodeanchor = cte.childnodeanchor
                          AND dh.dimensionspacepointhash = cte.dsp
                        WHERE
                          JSON_EXTRACT(dh.subtreetags, :tagPath) != TRUE
                      )
                    SELECT
                      *
                    FROM
                      cte
                  ) subquery ON h.dimensionspacepointhash = subquery.dsp
                  AND h.childnodeanchor = subquery.childnodeanchor
              ) AS h2
            WHERE
              NOT {$this->jsonEqualsSql('h2.subtreetags', 'h2.currentsubtreetags')}
            ON DUPLICATE KEY UPDATE subtreetags = VALUES (subtreetags)
            SQL;
        try {
            $this->dbal->executeStatement($removeTagStatement, [
                'nodeAggregateId' => $nodeAggregateId->value,
                'tagPath' => '$."' . $tag->value . '"',
                'targetContentStreamLayer' => $contentStreamLayers->getWriteLayer()->value,
                ...$allHierarchyStatement->getParameters()->toDbalValues(),
                ...$nestedHierarchyStatement->getParameters()->toDbalValues(),
            ], [
                ...$allHierarchyStatement->getParameters()->toDbalTypes(),
                ...$nestedHierarchyStatement->getParameters()->toDbalTypes(),
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to remove subtree tag %s for content stream %s, node aggregate id %s and dimension space points %s: %s', $tag->value, $contentStreamLayers->toDebugString(), $nodeAggregateId->value, $affectedDimensionSpacePoints->toJson(), $e->getMessage()), 1716482293, $e);
        }
    }

    private function moveSubtreeTags(ContentStreamLayers $contentStreamLayers, NodeAggregateId $newParentNodeAggregateId, DimensionSpacePoint $coveredDimensionSpacePoint): void
    {
        $hierarchyStatement = $this->statements->forHierarchyRelation($contentStreamLayers)->withDimensionSpacePoint($coveredDimensionSpacePoint);
        $moveSubtreeTagsStatement = <<<SQL
            INSERT INTO {$this->tableNames->hierarchyRelation()} (
              id, parentnodeanchor, childnodeanchor,
              position, subtreetags, dimensionspacepointhash,
              contentstreamlayer
            )
            SELECT
              h2.id,
              h2.parentnodeanchor,
              h2.childnodeanchor,
              h2.position,
              h2.subtreetags,
              h2.dimensionspacepointhash,
              h2.contentstreamlayer
            FROM
              (
                SELECT
                  h.id,
                  h.parentnodeanchor,
                  h.childnodeanchor,
                  h.position,
                  h.dimensionspacepointhash,
                  (
                    SELECT
                      JSON_MERGE_PATCH(
                        IFNULL(JSON_OBJECTAGG(htk.k, null), '{}'),
                        JSON_MERGE_PATCH('{}', h.subtreetags)
                      )
                    FROM
                      JSON_TABLE(r.subtreeTagsToInherit, '\$[*]' COLUMNS (k VARCHAR(36) PATH '\$')) htk
                  ) as subtreetags,
                  h.subtreetags as currentsubtreetags,
                  :targetContentStreamLayer as contentstreamlayer
                FROM
                  {$hierarchyStatement->toSql()} h
                  JOIN (
                    WITH
                      RECURSIVE cte AS (
                        SELECT
                          JSON_KEYS(th.subtreetags) subtreeTagsToInherit,
                          th.childnodeanchor
                        FROM
                          {$hierarchyStatement->toSql()} th
                          INNER JOIN {$this->tableNames->node()} tn ON tn.relationanchorpoint = th.childnodeanchor
                        WHERE
                          tn.nodeaggregateid = :newParentNodeAggregateId
                        UNION
                        SELECT
                          JSON_MERGE_PRESERVE(
                            cte.subtreeTagsToInherit,
                            JSON_KEYS(
                              JSON_MERGE_PATCH('{}', dh.subtreetags)
                            )
                          ) AS subtreeTagsToInherit,
                          dh.childnodeanchor
                        FROM
                          cte
                          JOIN {$hierarchyStatement->toSql()} dh ON dh.parentnodeanchor = cte.childnodeanchor
                      )
                    SELECT
                      *
                    FROM
                      cte
                  ) AS r
                WHERE
                  h.childnodeanchor = r.childnodeanchor
              ) AS h2
            WHERE
              NOT {$this->jsonEqualsSql('h2.subtreetags', 'h2.currentsubtreetags')}
            ON DUPLICATE KEY UPDATE subtreetags = VALUES(subtreetags)
            SQL;
        try {
            // Mysql hack, too eager to optimize https://dev.mysql.com/doc/refman/8.4/en/derived-table-optimization.html
            $this->dbal->executeQuery('set optimizer_switch="derived_merge=off"');
            $this->dbal->executeStatement($moveSubtreeTagsStatement, [
                'newParentNodeAggregateId' => $newParentNodeAggregateId->value,
                'targetContentStreamLayer' => $contentStreamLayers->getWriteLayer()->value,
                ...$hierarchyStatement->getParameters()->toDbalValues(),
            ], [
                ...$hierarchyStatement->getParameters()->toDbalTypes(),
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to move subtree tags for content stream %s, new parent node aggregate id %s and dimension space point %s: %s', $contentStreamLayers->toDebugString(), $newParentNodeAggregateId->value, $coveredDimensionSpacePoint->toJson(), $e->getMessage()), 1716482574, $e);
        }
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

    private function jsonEqualsSql(string $sqlExpressionA, string $sqlExpressionB): string
    {
        if ($this->dbal->getDatabasePlatform() instanceof MariaDBPlatform) {
            return sprintf('JSON_EQUALS(%s, %s)', $sqlExpressionA, $sqlExpressionB);
        } else {
            return sprintf('(CAST(%s AS JSON) = CAST(%s AS JSON))', $sqlExpressionA, $sqlExpressionB);
        }
    }
}
