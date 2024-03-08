<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Repository\NodeFactory;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Dto\SubtreeTag;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasTagged;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasUntagged;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodeSubtreeTags;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;

/**
 * The subtree tagging projection feature trait
 *
 * @internal
 */
trait SubtreeTagging
{
    abstract protected function getTableNamePrefix(): string;

    /**
     * @throws \Throwable
     */
    private function whenSubtreeWasTagged(SubtreeWasTagged $event): void
    {
        $this->getDatabaseConnection()->executeStatement('
            UPDATE ' . $this->getTableNamePrefix() . '_hierarchyrelation h
            SET h.subtreetags = JSON_INSERT(h.subtreetags, :tagPath, null)
            WHERE h.childnodeanchor IN (
              WITH RECURSIVE cte (id) AS (
                SELECT ch.childnodeanchor
                FROM ' . $this->getTableNamePrefix() . '_hierarchyrelation ch
                INNER JOIN ' . $this->getTableNamePrefix() . '_node n ON n.relationanchorpoint = ch.parentnodeanchor
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
                  JOIN ' . $this->getTableNamePrefix() . '_hierarchyrelation dh ON dh.parentnodeanchor = cte.id
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
            UPDATE ' . $this->getTableNamePrefix() . '_hierarchyrelation h
            INNER JOIN ' . $this->getTableNamePrefix() . '_node n ON n.relationanchorpoint = h.childnodeanchor
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
            UPDATE ' . $this->getTableNamePrefix() . '_hierarchyrelation h
            INNER JOIN ' . $this->getTableNamePrefix() . '_hierarchyrelation ph ON ph.childnodeanchor = h.parentnodeanchor
            SET h.subtreetags = IF((
              SELECT
                JSON_CONTAINS_PATH(ph.subtreetags, \'one\', :tagPath)
              FROM
                ' . $this->getTableNamePrefix() . '_hierarchyrelation ph
                INNER JOIN ' . $this->getTableNamePrefix() . '_hierarchyrelation ch ON ch.parentnodeanchor = ph.childnodeanchor
                INNER JOIN ' . $this->getTableNamePrefix() . '_node n ON n.relationanchorpoint = ch.childnodeanchor
              WHERE
                n.nodeaggregateid = :nodeAggregateId
                AND ph.contentstreamid = :contentStreamId
                AND ph.dimensionspacepointhash in (:dimensionSpacePointHashes)
              LIMIT 1
            ), JSON_SET(h.subtreetags, :tagPath, null), JSON_REMOVE(h.subtreetags, :tagPath))
            WHERE h.childnodeanchor IN (
              WITH RECURSIVE cte (id) AS (
                SELECT ch.childnodeanchor
                FROM ' . $this->getTableNamePrefix() . '_hierarchyrelation ch
                INNER JOIN ' . $this->getTableNamePrefix() . '_node n ON n.relationanchorpoint = ch.childnodeanchor
                WHERE
                  n.nodeaggregateid = :nodeAggregateId
                  AND ch.contentstreamid = :contentStreamId
                  AND ch.dimensionspacepointhash in (:dimensionSpacePointHashes)
                UNION ALL
                SELECT
                  dh.childnodeanchor
                FROM
                  cte
                  JOIN ' . $this->getTableNamePrefix() . '_hierarchyrelation dh ON dh.parentnodeanchor = cte.id
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

    private function moveSubtreeTags(ContentStreamId $contentStreamId, NodeAggregateId $nodeAggregateId, NodeAggregateId $newParentNodeAggregateId, DimensionSpacePoint $coveredDimensionSpacePoint): void
    {
        $nodeSubtreeTags = $this->subtreeTagsForNode($nodeAggregateId, $contentStreamId, $coveredDimensionSpacePoint);
        $newParentSubtreeTags = $this->subtreeTagsForNode($newParentNodeAggregateId, $contentStreamId, $coveredDimensionSpacePoint);
        $newSubtreeTags = [];
        foreach ($nodeSubtreeTags->withoutInherited() as $tag) {
            $newSubtreeTags[$tag->value] = true;
        }
        foreach ($newParentSubtreeTags as $tag) {
            $newSubtreeTags[$tag->value] = null;
        }
        if ($newSubtreeTags === [] && $nodeSubtreeTags->isEmpty()) {
            return;
        }
        $this->getDatabaseConnection()->executeStatement('
            UPDATE ' . $this->getTableNamePrefix() . '_hierarchyrelation h
            SET h.subtreetags = JSON_MERGE_PATCH(:newParentTags, JSON_MERGE_PATCH(\'{}\', h.subtreetags))
            WHERE h.childnodeanchor IN (
              WITH RECURSIVE cte (id) AS (
                SELECT ch.childnodeanchor
                FROM ' . $this->getTableNamePrefix() . '_hierarchyrelation ch
                INNER JOIN ' . $this->getTableNamePrefix() . '_node n ON n.relationanchorpoint = ch.parentnodeanchor
                WHERE
                  n.nodeaggregateid = :nodeAggregateId
                  AND ch.contentstreamid = :contentStreamId
                  AND ch.dimensionspacepointhash = :dimensionSpacePointHash
                UNION ALL
                SELECT
                  dh.childnodeanchor
                FROM
                  cte
                  JOIN ' . $this->getTableNamePrefix() . '_hierarchyrelation dh ON dh.parentnodeanchor = cte.id
              )
              SELECT id FROM cte
            )
            ', [
            'contentStreamId' => $contentStreamId->value,
            'nodeAggregateId' => $nodeAggregateId->value,
            'dimensionSpacePointHash' => $coveredDimensionSpacePoint->hash,
            'newParentTags' => json_encode($newSubtreeTags, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT),
        ]);
        $this->getDatabaseConnection()->executeStatement('
            UPDATE ' . $this->getTableNamePrefix() . '_hierarchyrelation h
            INNER JOIN ' . $this->getTableNamePrefix() . '_node n ON n.relationanchorpoint = h.childnodeanchor
            SET h.subtreetags = :newParentTags
            WHERE
              n.nodeaggregateid = :nodeAggregateId
              AND h.contentstreamid = :contentStreamId
              AND h.dimensionspacepointhash = :dimensionSpacePointHash
        ', [
            'contentStreamId' => $contentStreamId->value,
            'nodeAggregateId' => $nodeAggregateId->value,
            'dimensionSpacePointHash' => $coveredDimensionSpacePoint->hash,
            'newParentTags' => json_encode($newSubtreeTags, JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT),
        ]);
    }

    private function subtreeTagsForNode(NodeAggregateId $nodeAggregateId, ContentStreamId $contentStreamId, DimensionSpacePoint $dimensionSpacePoint): NodeSubtreeTags
    {
        $subtreeTagsJson = $this->getDatabaseConnection()->fetchOne('
                SELECT h.subtreetags FROM ' . $this->getTableNamePrefix() . '_hierarchyrelation h
                INNER JOIN ' . $this->getTableNamePrefix() . '_node n ON n.relationanchorpoint = h.childnodeanchor
                WHERE
                  n.nodeaggregateid = :nodeAggregateId
                  AND h.contentstreamid = :contentStreamId
                  AND h.dimensionspacepointhash = :dimensionSpacePointHash
            ', [
            'nodeAggregateId' => $nodeAggregateId->value,
            'contentStreamId' => $contentStreamId->value,
            'dimensionSpacePointHash' => $dimensionSpacePoint->hash,
        ]);
        if (!is_string($subtreeTagsJson)) {
            throw new \RuntimeException(sprintf('Failed to fetch SubtreeTags for node "%s" in content subgraph "%s@%s"', $nodeAggregateId->value, $dimensionSpacePoint->toJson(), $contentStreamId->value), 1698838865);
        }
        return NodeFactory::extractSubtreeTagsWithInheritedFromJson($subtreeTagsJson);
    }

    private function subtreeTagsForHierarchyRelation(ContentStreamId $contentStreamId, NodeRelationAnchorPoint $parentNodeAnchorPoint, DimensionSpacePoint $dimensionSpacePoint): NodeSubtreeTags
    {
        if ($parentNodeAnchorPoint->equals(NodeRelationAnchorPoint::forRootEdge())) {
            return NodeSubtreeTags::createEmpty();
        }
        $subtreeTagsJson = $this->getDatabaseConnection()->fetchOne('
                SELECT h.subtreetags FROM ' . $this->getTableNamePrefix() . '_hierarchyrelation h
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
        return NodeFactory::extractSubtreeTagsWithInheritedFromJson($subtreeTagsJson);
    }

    abstract protected function getDatabaseConnection(): Connection;
}
