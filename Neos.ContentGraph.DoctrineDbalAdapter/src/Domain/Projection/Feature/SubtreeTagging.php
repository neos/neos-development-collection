<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeTags;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * The subtree tagging projection feature trait
 *
 * @internal
 */
trait SubtreeTagging
{
    private function addSubtreeTag(ContentStreamId $contentStreamId, NodeAggregateId $nodeAggregateId, DimensionSpacePointSet $affectedDimensionSpacePoints, SubtreeTag $tag): void
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
                      AND ch.contentstreamid = :contentStreamId
                      AND ch.dimensionspacepointhash in (:dimensionSpacePointHashes)
                      AND NOT JSON_CONTAINS_PATH(ch.subtreetags, 'one', :tagPath)
                    UNION ALL
                    SELECT
                      dh.childnodeanchor,
                      dh.dimensionspacepointhash
                    FROM
                      cte
                      JOIN {$this->tableNames->hierarchyRelation()} dh ON dh.parentnodeanchor = cte.id
                        AND dh.contentstreamid = :contentStreamId
                        AND dh.dimensionspacepointhash = cte.dsp
                    WHERE
                      NOT JSON_CONTAINS_PATH(dh.subtreetags, 'one', :tagPath)
                )
                SELECT * FROM cte
            ) subquery ON h.dimensionspacepointhash = subquery.dsp
                AND h.childnodeanchor = subquery.id
            SET h.subtreetags = JSON_INSERT(h.subtreetags, :tagPath, null)
            WHERE h.contentstreamid = :contentStreamId
        SQL;

        try {
            $this->dbal->executeStatement($addTagToDescendantsStatement, [
                'contentStreamId' => $contentStreamId->value,
                'nodeAggregateId' => $nodeAggregateId->value,
                'dimensionSpacePointHashes' => $affectedDimensionSpacePoints->getPointHashes(),
                'tagPath' => '$."' . $tag->value . '"',
            ], [
                'dimensionSpacePointHashes' => ArrayParameterType::STRING,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('1: Failed to add subtree tag %s for content stream %s, node aggregate id %s and dimension space points %s: %s', $tag->value, $contentStreamId->value, $nodeAggregateId->value, $affectedDimensionSpacePoints->toJson(), $e->getMessage()), 1716479749, $e);
        }

        $addTagToNodeStatement = <<<SQL
            UPDATE {$this->tableNames->hierarchyRelation()} h
            INNER JOIN {$this->tableNames->node()} n ON n.relationanchorpoint = h.childnodeanchor
            SET h.subtreetags = JSON_SET(h.subtreetags, :tagPath, true)
            WHERE
              n.nodeaggregateid = :nodeAggregateId
              AND h.contentstreamid = :contentStreamId
              AND h.dimensionspacepointhash in (:dimensionSpacePointHashes)
        SQL;
        try {
            $this->dbal->executeStatement($addTagToNodeStatement, [
                'contentStreamId' => $contentStreamId->value,
                'nodeAggregateId' => $nodeAggregateId->value,
                'dimensionSpacePointHashes' => $affectedDimensionSpacePoints->getPointHashes(),
                'tagPath' => '$."' . $tag->value . '"',
            ], [
                'dimensionSpacePointHashes' => ArrayParameterType::STRING,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('2: Failed to add subtree tag %s for content stream %s, node aggregate id %s and dimension space points %s: %s', $tag->value, $contentStreamId->value, $nodeAggregateId->value, $affectedDimensionSpacePoints->toJson(), $e->getMessage()), 1716479840, $e);
        }
    }

    private function removeSubtreeTag(ContentStreamId $contentStreamId, NodeAggregateId $nodeAggregateId, DimensionSpacePointSet $affectedDimensionSpacePoints, SubtreeTag $tag): void
    {
        $removeTagStatement = <<<SQL
            UPDATE {$this->tableNames->hierarchyRelation()} h
            JOIN (
              WITH RECURSIVE cte (id, dsp, inheritsTag) AS (
                SELECT
                  ph.childnodeanchor,
                  ph.dimensionspacepointhash,
                  -- if the parent node of the affected node has the tag explicit or inherited we need to preserve its inheritance recursively when removing an explicit tag
                  JSON_CONTAINS_PATH(gph.subtreetags, 'one', :tagPath) as inheritsTag
                FROM {$this->tableNames->hierarchyRelation()} ph
                INNER JOIN {$this->tableNames->node()} n ON n.relationanchorpoint = ph.childnodeanchor
                INNER JOIN {$this->tableNames->hierarchyRelation()} gph ON gph.childnodeanchor = ph.parentnodeanchor AND gph.dimensionspacepointhash = ph.dimensionspacepointhash
                WHERE
                  n.nodeaggregateid = :nodeAggregateId
                  AND ph.contentstreamid = :contentStreamId
                  AND gph.contentstreamid = :contentStreamId
                  AND ph.dimensionspacepointhash in (:dimensionSpacePointHashes)
                UNION ALL
                SELECT
                  dh.childnodeanchor,
                  dh.dimensionspacepointhash,
                  -- if the entry node should inherit the tag, all its herby selected descendants do as well
                  cte.inheritsTag
                FROM
                  cte
                  JOIN {$this->tableNames->hierarchyRelation()} dh ON dh.parentnodeanchor = cte.id
                    AND dh.contentstreamid = :contentStreamId
                    AND dh.dimensionspacepointhash = cte.dsp
                WHERE
                  JSON_EXTRACT(dh.subtreetags, :tagPath) != TRUE
              )
              SELECT * FROM cte
            ) subquery ON h.dimensionspacepointhash = subquery.dsp
                AND h.childnodeanchor = subquery.id
                SET subtreetags = IF(subquery.inheritsTag, JSON_SET(subtreetags, :tagPath, null), JSON_REMOVE(subtreetags, :tagPath))
              WHERE contentstreamid = :contentStreamId
        SQL;
        try {
            $this->dbal->executeStatement($removeTagStatement, [
                'contentStreamId' => $contentStreamId->value,
                'nodeAggregateId' => $nodeAggregateId->value,
                'dimensionSpacePointHashes' => $affectedDimensionSpacePoints->getPointHashes(),
                'tagPath' => '$."' . $tag->value . '"',
            ], [
                'dimensionSpacePointHashes' => ArrayParameterType::STRING,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to remove subtree tag %s for content stream %s, node aggregate id %s and dimension space points %s: %s', $tag->value, $contentStreamId->value, $nodeAggregateId->value, $affectedDimensionSpacePoints->toJson(), $e->getMessage()), 1716482293, $e);
        }
    }

    private function moveSubtreeTags(ContentStreamId $contentStreamId, NodeAggregateId $newParentNodeAggregateId, DimensionSpacePoint $coveredDimensionSpacePoint): void
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
                  AND th.contentstreamid = :contentStreamId
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
                        AND dh.contentstreamid = :contentStreamId
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
              AND h.contentstreamid = :contentStreamId
              AND h.dimensionspacepointhash = :dimensionSpacePointHash
        SQL;
        try {
            // Mysql hack, too eager to optimize https://dev.mysql.com/doc/refman/8.4/en/derived-table-optimization.html
            $this->dbal->executeQuery('set optimizer_switch="derived_merge=off"');
            $this->dbal->executeStatement($moveSubtreeTagsStatement, [
                'contentStreamId' => $contentStreamId->value,
                'newParentNodeAggregateId' => $newParentNodeAggregateId->value,
                'dimensionSpacePointHash' => $coveredDimensionSpacePoint->hash,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to move subtree tags for content stream %s, new parent node aggregate id %s and dimension space point %s: %s', $contentStreamId->value, $newParentNodeAggregateId->value, $coveredDimensionSpacePoint->toJson(), $e->getMessage()), 1716482574, $e);
        }
    }

    private function subtreeTagsForHierarchyRelation(ContentStreamId $contentStreamId, NodeRelationAnchorPoint $parentNodeAnchorPoint, DimensionSpacePoint $dimensionSpacePoint): NodeTags
    {
        if ($parentNodeAnchorPoint->equals(NodeRelationAnchorPoint::forRootEdge())) {
            return NodeTags::createEmpty();
        }
        try {
            $subtreeTagsJson = $this->dbal->fetchOne('
                    SELECT h.subtreetags FROM ' . $this->tableNames->hierarchyRelation() . ' h
                    WHERE
                      h.childnodeanchor = :parentNodeAnchorPoint
                      AND h.contentstreamid = :contentStreamId
                      AND h.dimensionspacepointhash = :dimensionSpacePointHash
                ', [
                'parentNodeAnchorPoint' => $parentNodeAnchorPoint->value,
                'contentStreamId' => $contentStreamId->value,
                'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
            ]);
        } catch (DBALException $e) {
            throw new \RuntimeException(sprintf('Failed to fetch subtree tags for hierarchy parent anchor point "%s" in content subgraph "%s@%s": %s', $parentNodeAnchorPoint->value, $dimensionSpacePoint->toJson(), $contentStreamId->value, $e->getMessage()), 1716478760, $e);
        }
        if (!is_string($subtreeTagsJson)) {
            throw new \RuntimeException(sprintf('Failed to fetch subtree tags for hierarchy parent anchor point "%s" in content subgraph "%s@%s"', $parentNodeAnchorPoint->value, $dimensionSpacePoint->toJson(), $contentStreamId->value), 1704199847);
        }
        return NodeFactory::extractNodeTagsFromJson($subtreeTagsJson);
    }
}
