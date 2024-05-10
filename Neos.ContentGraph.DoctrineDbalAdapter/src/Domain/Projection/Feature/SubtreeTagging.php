<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasTagged;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasUntagged;
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
    /**
     * @throws \Throwable
     */
    private function whenSubtreeWasTagged(SubtreeWasTagged $event): void
    {
        $this->getDatabaseConnection()->executeStatement('
            UPDATE ' . $this->tableNames->hierachyRelation() . ' h
            SET h.subtreetags = JSON_INSERT(h.subtreetags, :tagPath, null)
            WHERE h.childnodeanchor IN (
              WITH RECURSIVE cte (id) AS (
                SELECT ch.childnodeanchor
                FROM ' . $this->tableNames->hierachyRelation() . ' ch
                INNER JOIN ' . $this->tableNames->node() . ' n ON n.relationanchorpoint = ch.parentnodeanchor
                WHERE
                  n.nodeaggregateid = :nodeAggregateId
                  AND ch.contentstreamid = :contentStreamId
                  AND ch.dimensionspacepointhash in (:dimensionSpacePointHashes)
                  AND NOT JSON_CONTAINS_PATH(ch.subtreetags, \'one\', :tagPath)
                UNION ALL
                SELECT
                  dh.childnodeanchor
                FROM
                  cte
                  JOIN ' . $this->tableNames->hierachyRelation() . ' dh ON dh.parentnodeanchor = cte.id
                WHERE
                  NOT JSON_CONTAINS_PATH(dh.subtreetags, \'one\', :tagPath)
              )
              SELECT DISTINCT id FROM cte
            )
              AND h.contentstreamid = :contentStreamId
              AND h.dimensionspacepointhash in (:dimensionSpacePointHashes)
            ', [
                'contentStreamId' => $event->contentStreamId->value,
                'nodeAggregateId' => $event->nodeAggregateId->value,
                'dimensionSpacePointHashes' => $event->affectedDimensionSpacePoints->getPointHashes(),
                'tagPath' => '$.' . $event->tag->value,
            ], [
                'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY,
            ]);

        $this->getDatabaseConnection()->executeStatement('
            UPDATE ' . $this->tableNames->hierachyRelation() . ' h
            INNER JOIN ' . $this->tableNames->node() . ' n ON n.relationanchorpoint = h.childnodeanchor
            SET h.subtreetags = JSON_SET(h.subtreetags, :tagPath, true)
            WHERE
              n.nodeaggregateid = :nodeAggregateId
              AND h.contentstreamid = :contentStreamId
              AND h.dimensionspacepointhash in (:dimensionSpacePointHashes)
        ', [
            'contentStreamId' => $event->contentStreamId->value,
            'nodeAggregateId' => $event->nodeAggregateId->value,
            'dimensionSpacePointHashes' => $event->affectedDimensionSpacePoints->getPointHashes(),
            'tagPath' => '$.' . $event->tag->value,
        ], [
            'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY,
        ]);
    }

    /**
     * @throws \Throwable
     */
    private function whenSubtreeWasUntagged(SubtreeWasUntagged $event): void
    {
        $this->getDatabaseConnection()->executeStatement('
            UPDATE ' . $this->tableNames->hierachyRelation() . ' h
            INNER JOIN ' . $this->tableNames->hierachyRelation() . ' ph ON ph.childnodeanchor = h.parentnodeanchor
            SET h.subtreetags = IF((
              SELECT
                JSON_CONTAINS_PATH(ph.subtreetags, \'one\', :tagPath)
              FROM
                ' . $this->tableNames->hierachyRelation() . ' ph
                INNER JOIN ' . $this->tableNames->hierachyRelation() . ' ch ON ch.parentnodeanchor = ph.childnodeanchor
                INNER JOIN ' . $this->tableNames->node() . ' n ON n.relationanchorpoint = ch.childnodeanchor
              WHERE
                n.nodeaggregateid = :nodeAggregateId
                AND ph.contentstreamid = :contentStreamId
                AND ph.dimensionspacepointhash in (:dimensionSpacePointHashes)
              LIMIT 1
            ), JSON_SET(h.subtreetags, :tagPath, null), JSON_REMOVE(h.subtreetags, :tagPath))
            WHERE h.childnodeanchor IN (
              WITH RECURSIVE cte (id) AS (
                SELECT ch.childnodeanchor
                FROM ' . $this->tableNames->hierachyRelation() . ' ch
                INNER JOIN ' . $this->tableNames->node() . ' n ON n.relationanchorpoint = ch.childnodeanchor
                WHERE
                  n.nodeaggregateid = :nodeAggregateId
                  AND ch.contentstreamid = :contentStreamId
                  AND ch.dimensionspacepointhash in (:dimensionSpacePointHashes)
                UNION ALL
                SELECT
                  dh.childnodeanchor
                FROM
                  cte
                  JOIN ' . $this->tableNames->hierachyRelation() . ' dh ON dh.parentnodeanchor = cte.id
                WHERE
                  JSON_EXTRACT(dh.subtreetags, :tagPath) != TRUE
              )
              SELECT DISTINCT id FROM cte
            )
              AND h.contentstreamid = :contentStreamId
              AND h.dimensionspacepointhash in (:dimensionSpacePointHashes)
            ', [
            'contentStreamId' => $event->contentStreamId->value,
            'nodeAggregateId' => $event->nodeAggregateId->value,
            'dimensionSpacePointHashes' => $event->affectedDimensionSpacePoints->getPointHashes(),
            'tagPath' => '$.' . $event->tag->value,
        ], [
            'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY,
        ]);
    }

    private function moveSubtreeTags(
        ContentStreamId $contentStreamId,
        NodeAggregateId $newParentNodeAggregateId,
        DimensionSpacePoint $coveredDimensionSpacePoint
    ): void {
        $this->getDatabaseConnection()->executeStatement('
            UPDATE ' . $this->tableNames->hierachyRelation() . ' h,
            (
              WITH RECURSIVE cte AS (
                SELECT
                  JSON_KEYS(th.subtreetags) subtreeTagsToInherit, th.childnodeanchor
                FROM
                  ' . $this->tableNames->hierachyRelation() . ' th
                  INNER JOIN ' . $this->tableNames->node() . ' tn ON tn.relationanchorpoint = th.childnodeanchor
                WHERE
                  tn.nodeaggregateid = :newParentNodeAggregateId
                  AND th.contentstreamid = :contentStreamId
                  AND th.dimensionspacepointhash = :dimensionSpacePointHash
                UNION
                SELECT
                    JSON_MERGE_PRESERVE(
                        cte.subtreeTagsToInherit,
                        JSON_KEYS(JSON_MERGE_PATCH(
                            \'{}\',
                            dh.subtreetags
                        ))
                    ) subtreeTagsToInherit,
                    dh.childnodeanchor
                FROM
                  cte
                JOIN ' . $this->tableNames->hierachyRelation() . ' dh
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
                    IFNULL(JSON_OBJECTAGG(htk.k, null), \'{}\'),
                    JSON_MERGE_PATCH(\'{}\', h.subtreetags)
                )
              FROM
                JSON_TABLE(r.subtreeTagsToInherit, \'$[*]\' COLUMNS (k VARCHAR(36) PATH \'$\')) htk
            )
            WHERE
              h.childnodeanchor = r.childnodeanchor
              AND h.contentstreamid = :contentStreamId
              AND h.dimensionspacepointhash = :dimensionSpacePointHash
            ', [
            'contentStreamId' => $contentStreamId->value,
            'newParentNodeAggregateId' => $newParentNodeAggregateId->value,
            'dimensionSpacePointHash' => $coveredDimensionSpacePoint->hash,
        ]);
    }

    private function subtreeTagsForHierarchyRelation(ContentStreamId $contentStreamId, NodeRelationAnchorPoint $parentNodeAnchorPoint, DimensionSpacePoint $dimensionSpacePoint): NodeTags
    {
        if ($parentNodeAnchorPoint->equals(NodeRelationAnchorPoint::forRootEdge())) {
            return NodeTags::createEmpty();
        }
        $subtreeTagsJson = $this->getDatabaseConnection()->fetchOne('
                SELECT h.subtreetags FROM ' . $this->tableNames->hierachyRelation() . ' h
                WHERE
                  h.childnodeanchor = :parentNodeAnchorPoint
                  AND h.contentstreamid = :contentStreamId
                  AND h.dimensionspacepointhash = :dimensionSpacePointHash
            ', [
            'parentNodeAnchorPoint' => $parentNodeAnchorPoint->value,
            'contentStreamId' => $contentStreamId->value,
            'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
        ]);
        if (!is_string($subtreeTagsJson)) {
            throw new \RuntimeException(sprintf('Failed to fetch SubtreeTags for hierarchy parent anchor point "%s" in content subgraph "%s@%s"', $parentNodeAnchorPoint->value, $dimensionSpacePoint->toJson(), $contentStreamId->value), 1704199847);
        }
        return NodeFactory::extractNodeTagsFromJson($subtreeTagsJson);
    }

    abstract protected function getDatabaseConnection(): Connection;
}
