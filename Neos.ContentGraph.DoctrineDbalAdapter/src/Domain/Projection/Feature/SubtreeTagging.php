<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasTagged;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasUntagged;

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
        $this->transactional(function () use ($event) {
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
                  SELECT id FROM cte
                )
                ', [
                    'contentStreamId' => $event->contentStreamId->value,
                    'nodeAggregateId' => $event->nodeAggregateId->value,
                    'dimensionSpacePointHashes' => $event->affectedDimensionSpacePoints->getPointHashes(),
                    'tagPath' => '$.' . $event->tag->value,
                ], [
                    'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY,
                ]
            );

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

            if ($event->tag->value === 'disabled') {

                // TODO: still unsure why we need an "INSERT IGNORE" here;
                // normal "INSERT" can trigger a duplicate key constraint exception
                $this->getDatabaseConnection()->executeStatement(
                    '
-- GraphProjector::whenSubtreeWasTagged
insert ignore into ' . $this->getTableNamePrefix() . '_restrictionrelation
    (contentstreamid, dimensionspacepointhash, originnodeaggregateid, affectednodeaggregateid)

    -- we build a recursive tree
    with recursive tree as (
         -- --------------------------------
         -- INITIAL query: select the root nodes of the tree; as given in $menuLevelNodeIds
         -- --------------------------------
         select
            n.relationanchorpoint,
            n.nodeaggregateid,
            h.dimensionspacepointhash
         from
            ' . $this->getTableNamePrefix() . '_node n
         -- we need to join with the hierarchy relation, because we need the dimensionspacepointhash.
         inner join ' . $this->getTableNamePrefix() . '_hierarchyrelation h
            on h.childnodeanchor = n.relationanchorpoint
         where
            n.nodeaggregateid = :entryNodeAggregateId
            and h.contentstreamid = :contentStreamId
            and h.dimensionspacepointhash in (:dimensionSpacePointHashes)
    union
         -- --------------------------------
         -- RECURSIVE query: do one "child" query step
         -- --------------------------------
         select
            c.relationanchorpoint,
            c.nodeaggregateid,
            h.dimensionspacepointhash
         from
            tree p
         inner join ' . $this->getTableNamePrefix() . '_hierarchyrelation h
            on h.parentnodeanchor = p.relationanchorpoint
         inner join ' . $this->getTableNamePrefix() . '_node c
            on h.childnodeanchor = c.relationanchorpoint
         where
            h.contentstreamid = :contentStreamId
            and h.dimensionspacepointhash in (:dimensionSpacePointHashes)
    )

    select
        "' . $event->contentStreamId->value . '" as contentstreamid,
        dimensionspacepointhash,
        "' . $event->nodeAggregateId->value . '" as originnodeaggregateid,
        nodeaggregateid as affectednodeaggregateid
    from tree
            ',
                    [
                        'entryNodeAggregateId' => $event->nodeAggregateId->value,
                        'contentStreamId' => $event->contentStreamId->value,
                        'dimensionSpacePointHashes' => $event->affectedDimensionSpacePoints->getPointHashes()
                    ],
                    [
                        'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
                    ]
                );
            }
        });
    }

    /**
     * @throws \Throwable
     */
    private function whenSubtreeWasUntagged(SubtreeWasUntagged $event): void
    {
        $this->transactional(function () use ($event) {
            $this->removeOutgoingRestrictionRelationsOfNodeAggregateInDimensionSpacePoints(
                $event->contentStreamId,
                $event->nodeAggregateId,
                $event->affectedDimensionSpacePoints
            );
        });
    }

    abstract protected function getDatabaseConnection(): Connection;

    abstract protected function transactional(\Closure $operations): void;
}
