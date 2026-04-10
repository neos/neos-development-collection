<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\ContentStreamDbIds;
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
    private function addSubtreeTag(ContentStreamDbIds $contentStreamDbIds, NodeAggregateId $nodeAggregateId, DimensionSpacePointSet $affectedDimensionSpacePoints, SubtreeTag $tag): void
    {
        $addTagToDescendantsStatement = <<<SQL
        UPDATE {$this->tableNames->hierarchyRelation()} h
            JOIN (
                WITH RECURSIVE cte (id, dsp) AS (
                    SELECT ch.childnodeanchor, ch.dimensionspacepointhash
                    FROM {$this->tableNames->hierarchyRelation()} ch
                    INNER JOIN {$this->tableNames->node()} n ON n.relationanchorpoint = ch.parentnodeanchor
                    WHERE
                      n.nodeaggregateid = :nodeAggregateId
                      AND ch.contentstreamdbid IN (:contentStreamDbIds)
                      AND ch.dimensionspacepointhash in (:dimensionSpacePointHashes)
                      AND NOT JSON_CONTAINS_PATH(ch.subtreetags, 'one', :tagPath)
                    UNION ALL
                    SELECT
                      dh.childnodeanchor,
                      dh.dimensionspacepointhash
                    FROM
                      cte
                      JOIN {$this->tableNames->hierarchyRelation()} dh ON dh.parentnodeanchor = cte.id
                        AND dh.contentstreamdbid IN (:contentStreamDbIds)
                        AND dh.dimensionspacepointhash = cte.dsp
                    WHERE
                      NOT JSON_CONTAINS_PATH(dh.subtreetags, 'one', :tagPath)
                )
                SELECT * FROM cte
            ) subquery ON h.dimensionspacepointhash = subquery.dsp
                AND h.childnodeanchor = subquery.id
            SET h.subtreetags = JSON_INSERT(h.subtreetags, :tagPath, null)
            WHERE h.contentstreamdbid IN (:contentStreamDbIds)
        SQL;

        try {
            $this->dbal->executeStatement($addTagToDescendantsStatement, [
                'contentStreamDbIds' => $contentStreamDbIds->toIntArray(),
                'nodeAggregateId' => $nodeAggregateId->value,
                'dimensionSpacePointHashes' => $affectedDimensionSpacePoints->getPointHashes(),
                'tagPath' => '$."' . $tag->value . '"',
            ], [
                'dimensionSpacePointHashes' => ArrayParameterType::STRING,
                'contentStreamDbIds' => ArrayParameterType::INTEGER,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('1: Failed to add subtree tag %s for content stream %s, node aggregate id %s and dimension space points %s: %s', $tag->value, $contentStreamDbIds->toDebugString(), $nodeAggregateId->value, $affectedDimensionSpacePoints->toJson(), $e->getMessage()), 1716479749, $e);
        }

        $addTagToNodeStatement = <<<SQL
            UPDATE {$this->tableNames->hierarchyRelation()} h
            INNER JOIN {$this->tableNames->node()} n ON n.relationanchorpoint = h.childnodeanchor
            SET h.subtreetags = JSON_SET(h.subtreetags, :tagPath, true)
            WHERE
              n.nodeaggregateid = :nodeAggregateId
              AND h.contentstreamdbid IN (:contentStreamDbIds)
              AND h.dimensionspacepointhash in (:dimensionSpacePointHashes)
        SQL;
        try {
            $this->dbal->executeStatement($addTagToNodeStatement, [
                'contentStreamDbIds' => $contentStreamDbIds->toIntArray(),
                'nodeAggregateId' => $nodeAggregateId->value,
                'dimensionSpacePointHashes' => $affectedDimensionSpacePoints->getPointHashes(),
                'tagPath' => '$."' . $tag->value . '"',
            ], [
                'dimensionSpacePointHashes' => ArrayParameterType::STRING,
                'contentStreamDbIds' => ArrayParameterType::INTEGER,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('2: Failed to add subtree tag %s for content stream %s, node aggregate id %s and dimension space points %s: %s', $tag->value, $contentStreamDbIds->toDebugString(), $nodeAggregateId->value, $affectedDimensionSpacePoints->toJson(), $e->getMessage()), 1716479840, $e);
        }
    }

    private function removeSubtreeTag(ContentStreamDbIds $contentStreamDbIds, NodeAggregateId $nodeAggregateId, DimensionSpacePointSet $affectedDimensionSpacePoints, SubtreeTag $tag): void
    {
        $removeTagStatement = <<<SQL
            UPDATE {$this->tableNames->hierarchyRelation()} h
            JOIN (
              WITH RECURSIVE cte (id, dsp) AS (
                SELECT ph.childnodeanchor, ph.dimensionspacepointhash
                FROM {$this->tableNames->hierarchyRelation()} ph
                INNER JOIN {$this->tableNames->node()} n ON n.relationanchorpoint = ph.childnodeanchor
                WHERE
                  n.nodeaggregateid = :nodeAggregateId
                  AND ph.contentstreamdbid IN (:contentStreamDbIds)
                  AND ph.dimensionspacepointhash in (:dimensionSpacePointHashes)
                UNION ALL
                SELECT
                  dh.childnodeanchor,
                  dh.dimensionspacepointhash
                FROM
                  cte
                  JOIN {$this->tableNames->hierarchyRelation()} dh ON dh.parentnodeanchor = cte.id
                    AND dh.contentstreamdbid IN (:contentStreamDbIds)
                    AND dh.dimensionspacepointhash = cte.dsp
                WHERE
                  JSON_EXTRACT(dh.subtreetags, :tagPath) != TRUE
              )
              SELECT * FROM cte
            ) subquery ON h.dimensionspacepointhash = subquery.dsp
                AND h.childnodeanchor = subquery.id
                SET subtreetags = IF(
                (
                    SELECT containsTag FROM (SELECT
                        JSON_CONTAINS_PATH(gph.subtreetags, 'one', :tagPath) as containsTag
                  FROM
                    {$this->tableNames->hierarchyRelation()} gph
                    INNER JOIN {$this->tableNames->hierarchyRelation()} ph ON ph.parentnodeanchor = gph.childnodeanchor
                    INNER JOIN {$this->tableNames->node()} n ON n.relationanchorpoint = ph.childnodeanchor
                  WHERE
                    ph.parentnodeanchor = gph.childnodeanchor
                    AND n.nodeaggregateid = :nodeAggregateId
                    AND gph.contentstreamdbid IN (:contentStreamDbIds)
                  LIMIT 1) as containsTagSubQuery
                ), JSON_SET(subtreetags, :tagPath, null), JSON_REMOVE(subtreetags, :tagPath)
              )
              WHERE contentstreamdbid IN (:contentStreamDbIds)
        SQL;
        try {
            $this->dbal->executeStatement($removeTagStatement, [
                'contentStreamDbIds' => $contentStreamDbIds->toIntArray(),
                'nodeAggregateId' => $nodeAggregateId->value,
                'dimensionSpacePointHashes' => $affectedDimensionSpacePoints->getPointHashes(),
                'tagPath' => '$."' . $tag->value . '"',
            ], [
                'dimensionSpacePointHashes' => ArrayParameterType::STRING,
                'contentStreamDbIds' => ArrayParameterType::INTEGER,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to remove subtree tag %s for content stream %s, node aggregate id %s and dimension space points %s: %s', $tag->value, $contentStreamDbIds->toDebugString(), $nodeAggregateId->value, $affectedDimensionSpacePoints->toJson(), $e->getMessage()), 1716482293, $e);
        }
    }

    private function moveSubtreeTags(ContentStreamDbIds $contentStreamDbIds, NodeAggregateId $newParentNodeAggregateId, DimensionSpacePoint $coveredDimensionSpacePoint): void
    {
        $moveSubtreeTagsStatement = <<<SQL
            UPDATE {$this->tableNames->hierarchyRelation()} h,
            (
              WITH RECURSIVE cte AS (
                SELECT
                  JSON_KEYS(th.subtreetags) subtreeTagsToInherit, th.childnodeanchor
                FROM
                  {$this->tableNames->hierarchyRelation()} th
                  INNER JOIN {$this->tableNames->node()} tn ON tn.relationanchorpoint = th.childnodeanchor
                WHERE
                  tn.nodeaggregateid = :newParentNodeAggregateId
                  AND th.contentstreamdbid IN (:contentStreamDbIds)
                  AND th.dimensionspacepointhash = :dimensionSpacePointHash
                UNION
                SELECT
                    JSON_MERGE_PRESERVE(
                        cte.subtreeTagsToInherit,
                        JSON_KEYS(JSON_MERGE_PATCH(
                            '{}',
                            dh.subtreetags
                        ))
                    ) AS subtreeTagsToInherit,
                    dh.childnodeanchor
                FROM
                  cte
                JOIN {$this->tableNames->hierarchyRelation()} dh
                    ON
                        dh.parentnodeanchor = cte.childnodeanchor
                        AND dh.contentstreamdbid IN (:contentStreamDbIds)
                        AND dh.dimensionspacepointhash = :dimensionSpacePointHash
              )
              SELECT * FROM cte
            ) AS r
            SET h.subtreetags = (
              SELECT
                JSON_MERGE_PATCH(
                    IFNULL(JSON_OBJECTAGG(htk.k, null), '{}'),
                    JSON_MERGE_PATCH('{}', h.subtreetags)
                )
              FROM
                JSON_TABLE(r.subtreeTagsToInherit, '\$[*]' COLUMNS (k VARCHAR(36) PATH '\$')) htk
            )
            WHERE
              h.childnodeanchor = r.childnodeanchor
              AND h.contentstreamdbid IN (:contentStreamDbIds)
              AND h.dimensionspacepointhash = :dimensionSpacePointHash
        SQL;
        try {
            // Mysql hack, too eager to optimize https://dev.mysql.com/doc/refman/8.4/en/derived-table-optimization.html
            $this->dbal->executeQuery('set optimizer_switch="derived_merge=off"');
            $this->dbal->executeStatement($moveSubtreeTagsStatement, [
                'contentStreamDbIds' => $contentStreamDbIds->toIntArray(),
                'newParentNodeAggregateId' => $newParentNodeAggregateId->value,
                'dimensionSpacePointHash' => $coveredDimensionSpacePoint->hash,
            ], [
                'contentStreamDbIds' => ArrayParameterType::INTEGER,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to move subtree tags for content stream %s, new parent node aggregate id %s and dimension space point %s: %s', $contentStreamDbIds->toDebugString(), $newParentNodeAggregateId->value, $coveredDimensionSpacePoint->toJson(), $e->getMessage()), 1716482574, $e);
        }
    }

    private function subtreeTagsForHierarchyRelation(ContentStreamDbIds $contentStreamDbIds, NodeRelationAnchorPoint $parentNodeAnchorPoint, DimensionSpacePoint $dimensionSpacePoint): NodeTags
    {
        if ($parentNodeAnchorPoint->equals(NodeRelationAnchorPoint::forRootEdge())) {
            return NodeTags::createEmpty();
        }
        try {
            $subtreeTagsJson = $this->dbal->fetchOne('
                    SELECT h.subtreetags FROM ' . $this->tableNames->hierarchyRelation() . ' h
                    WHERE
                      h.childnodeanchor = :parentNodeAnchorPoint
                      AND h.contentstreamdbid IN (:contentStreamDbIds)
                      AND h.dimensionspacepointhash = :dimensionSpacePointHash
                ', [
                'parentNodeAnchorPoint' => $parentNodeAnchorPoint->value,
                'contentStreamDbIds' => $contentStreamDbIds->toIntArray(),
                'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
            ], [
                'contentStreamDbIds' => ArrayParameterType::INTEGER,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch subtree tags for hierarchy parent anchor point "%s" in content subgraph "%s@%s": %s', $parentNodeAnchorPoint->value, $dimensionSpacePoint->toJson(), $contentStreamDbIds->toDebugString(), $e->getMessage()), 1716478760, $e);
        }
        if (!is_string($subtreeTagsJson)) {
            throw new \RuntimeException(sprintf('Failed to fetch subtree tags for hierarchy parent anchor point "%s" in content subgraph "%s@%s"', $parentNodeAnchorPoint->value, $dimensionSpacePoint->toJson(), $contentStreamDbIds->value), 1704199847);
        }
        return NodeFactory::extractNodeTagsFromJson($subtreeTagsJson);
    }
}
