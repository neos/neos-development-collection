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
          {$this->hierarchyRelationStatement->toSql()} h
          JOIN (
            -- todo use new id?
            WITH
              RECURSIVE cte (id, dsp) AS (
                SELECT
                  ch.childnodeanchor,
                  ch.dimensionspacepointhash
                FROM
                  {$this->hierarchyRelationStatement
                    ->where('h.dimensionspacepointhash in (:dimensionSpacePointHashes)')
                    ->andWhere('NOT JSON_CONTAINS_PATH(h.subtreetags, \'one\', :tagPath)')
                    ->toSql()} ch
                  INNER JOIN {$this->tableNames->node()} n ON n.relationanchorpoint = ch.parentnodeanchor
                WHERE
                  n.nodeaggregateid = :nodeAggregateId
                UNION ALL
                SELECT
                  dh.childnodeanchor,
                  dh.dimensionspacepointhash
                FROM
                  cte
                  JOIN {$this->hierarchyRelationStatement->toSql()} dh ON dh.parentnodeanchor = cte.id -- todo why not in where???? or why not to dimensionSpacePointHashes
                  AND dh.dimensionspacepointhash = cte.dsp
                WHERE
                  NOT JSON_CONTAINS_PATH(dh.subtreetags, 'one', :tagPath)
              )
            SELECT
              *
            FROM
              cte
          ) subquery ON h.dimensionspacepointhash = subquery.dsp
          AND h.childnodeanchor = subquery.id
        ON DUPLICATE KEY UPDATE subtreetags = VALUES(subtreetags)
        SQL;

        try {
            $this->dbal->executeStatement($addTagToDescendantsStatement, [
                'contentStreamLayers' => $contentStreamLayers->toIntArray(),
                'nodeAggregateId' => $nodeAggregateId->value,
                'dimensionSpacePointHashes' => $affectedDimensionSpacePoints->getPointHashes(),
                'tagPath' => '$."' . $tag->value . '"',
                'targetContentStreamLayer' => $contentStreamLayers->getWriteLayer()->value,
            ], [
                'dimensionSpacePointHashes' => ArrayParameterType::STRING,
                'contentStreamLayers' => ArrayParameterType::INTEGER,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('1: Failed to add subtree tag %s for content stream %s, node aggregate id %s and dimension space points %s: %s', $tag->value, $contentStreamLayers->toDebugString(), $nodeAggregateId->value, $affectedDimensionSpacePoints->toJson(), $e->getMessage()), 1716479749, $e);
        }

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
          {$this->hierarchyRelationStatement->where('h.dimensionspacepointhash in (:dimensionSpacePointHashes)')->toSql()} h
          INNER JOIN {$this->tableNames->node()} n ON n.relationanchorpoint = h.childnodeanchor
        WHERE
          n.nodeaggregateid = :nodeAggregateId
        ON DUPLICATE KEY UPDATE subtreetags = VALUES(subtreetags)
        SQL;
        try {
            $this->dbal->executeStatement($addTagToNodeStatement, [
                'contentStreamLayers' => $contentStreamLayers->toIntArray(),
                'nodeAggregateId' => $nodeAggregateId->value,
                'dimensionSpacePointHashes' => $affectedDimensionSpacePoints->getPointHashes(),
                'tagPath' => '$."' . $tag->value . '"',
                'targetContentStreamLayer' => $contentStreamLayers->getWriteLayer()->value,
            ], [
                'dimensionSpacePointHashes' => ArrayParameterType::STRING,
                'contentStreamLayers' => ArrayParameterType::INTEGER,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('2: Failed to add subtree tag %s for content stream %s, node aggregate id %s and dimension space points %s: %s', $tag->value, $contentStreamLayers->toDebugString(), $nodeAggregateId->value, $affectedDimensionSpacePoints->toJson(), $e->getMessage()), 1716479840, $e);
        }
    }

    private function removeSubtreeTag(ContentStreamLayers $contentStreamLayers, NodeAggregateId $nodeAggregateId, DimensionSpacePointSet $affectedDimensionSpacePoints, SubtreeTag $tag): void
    {
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
                        {$this->hierarchyRelationStatement->toSql()} gph
                        INNER JOIN {$this->hierarchyRelationStatement->toSql()} ph ON ph.parentnodeanchor = gph.childnodeanchor
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
              {$this->hierarchyRelationStatement->toSql()} h
              JOIN (
                -- todo use new actual id?
                WITH
                  RECURSIVE cte (id, dsp) AS (
                    SELECT
                      ph.childnodeanchor,
                      ph.dimensionspacepointhash
                    FROM
                      {$this->hierarchyRelationStatement->where('h.dimensionspacepointhash in (:dimensionSpacePointHashes)')->toSql()} ph
                      INNER JOIN {$this->tableNames->node()} n ON n.relationanchorpoint = ph.childnodeanchor
                    WHERE
                      n.nodeaggregateid = :nodeAggregateId
                    UNION ALL
                    SELECT
                      dh.childnodeanchor,
                      dh.dimensionspacepointhash
                    FROM
                      cte
                      JOIN {$this->hierarchyRelationStatement->toSql()} dh ON dh.parentnodeanchor = cte.id
                      AND dh.dimensionspacepointhash = cte.dsp
                    WHERE
                      JSON_EXTRACT(dh.subtreetags, :tagPath) != TRUE
                  )
                SELECT
                  *
                FROM
                  cte
              ) subquery ON h.dimensionspacepointhash = subquery.dsp
              AND h.childnodeanchor = subquery.id
          ) AS h2
        WHERE
          NOT {$this->jsonEqualsSql('h2.subtreetags', 'h2.currentsubtreetags')}
        ON DUPLICATE KEY UPDATE subtreetags = VALUES (subtreetags)
        SQL;
        try {
            $this->dbal->executeStatement($removeTagStatement, [
                'contentStreamLayers' => $contentStreamLayers->toIntArray(),
                'nodeAggregateId' => $nodeAggregateId->value,
                'dimensionSpacePointHashes' => $affectedDimensionSpacePoints->getPointHashes(),
                'tagPath' => '$."' . $tag->value . '"',
                'targetContentStreamLayer' => $contentStreamLayers->getWriteLayer()->value,
            ], [
                'dimensionSpacePointHashes' => ArrayParameterType::STRING,
                'contentStreamLayers' => ArrayParameterType::INTEGER,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to remove subtree tag %s for content stream %s, node aggregate id %s and dimension space points %s: %s', $tag->value, $contentStreamLayers->toDebugString(), $nodeAggregateId->value, $affectedDimensionSpacePoints->toJson(), $e->getMessage()), 1716482293, $e);
        }
    }

    private function moveSubtreeTags(ContentStreamLayers $contentStreamLayers, NodeAggregateId $newParentNodeAggregateId, DimensionSpacePoint $coveredDimensionSpacePoint): void
    {
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
              {$this->hierarchyRelationStatement->where('h.dimensionspacepointhash = :dimensionSpacePointHash')->toSql()} h
              JOIN (
                WITH
                  RECURSIVE cte AS (
                    SELECT
                      JSON_KEYS(th.subtreetags) subtreeTagsToInherit,
                      th.childnodeanchor
                    FROM
                      {$this->hierarchyRelationStatement->where('h.dimensionspacepointhash = :dimensionSpacePointHash')->toSql()} th
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
                      JOIN {$this->hierarchyRelationStatement->where('h.dimensionspacepointhash = :dimensionSpacePointHash')->toSql()} dh ON dh.parentnodeanchor = cte.childnodeanchor
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
                'contentStreamLayers' => $contentStreamLayers->toIntArray(),
                'newParentNodeAggregateId' => $newParentNodeAggregateId->value,
                'dimensionSpacePointHash' => $coveredDimensionSpacePoint->hash,
                'targetContentStreamLayer' => $contentStreamLayers->getWriteLayer()->value,
            ], [
                'contentStreamLayers' => ArrayParameterType::INTEGER,
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

        $subtreeTagsStatement = <<<SQL
        SELECT h.subtreetags FROM {$this->hierarchyRelationStatement->where('h.dimensionspacepointhash = :dimensionSpacePointHash')->toSql()} h
            WHERE h.childnodeanchor = :parentNodeAnchorPoint
        SQL;

        try {
            $subtreeTagsJson = $this->dbal->fetchOne($subtreeTagsStatement, [
                'parentNodeAnchorPoint' => $parentNodeAnchorPoint->value,
                'contentStreamLayers' => $contentStreamLayers->toIntArray(),
                'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
            ], [
                'contentStreamLayers' => ArrayParameterType::INTEGER,
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
