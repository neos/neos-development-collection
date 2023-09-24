<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Feature\NodeAttributes\Event\NodeAggregateAttributeWasAdded;

/**
 * The NodeDisabling projection feature trait
 *
 * @internal
 */
trait NodeDisabling
{
    abstract protected function getTableNamePrefix(): string;

    /**
     * @throws \Throwable
     */
    private function whenNodeAggregateAttributeWasAdded(NodeAggregateAttributeWasAdded $event): void
    {
        $this->transactional(function () use ($event) {


            // TODO: still unsure why we need an "INSERT IGNORE" here;
            // normal "INSERT" can trigger a duplicate key constraint exception
            $this->getDatabaseConnection()->executeStatement(
                '
-- GraphProjector::whenNodeAggregateAttributeWasAdded
insert ignore into ' . $this->getTableNamePrefix() . '_attribute
    (contentstreamid, dimensionspacepointhash, originnodeaggregateid, affectednodeaggregateid, value)

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
        :contentStreamId as contentstreamid,
        dimensionspacepointhash,
        :originNodeAggregateId as originnodeaggregateid,
        nodeaggregateid as affectednodeaggregateid,
        :attributeValue as value
    from tree
            ',
                [
                    'entryNodeAggregateId' => $event->nodeAggregateId->value,
                    'contentStreamId' => $event->contentStreamId->value,
                    'dimensionSpacePointHashes' => $event->affectedDimensionSpacePoints->getPointHashes(),
                    'originNodeAggregateId' => $event->nodeAggregateId->value,
                    'attributeValue' => $event->attribute->value,
                ],
                [
                    'dimensionSpacePointHashes' => Connection::PARAM_STR_ARRAY
                ]
            );
        });
    }

    abstract protected function getDatabaseConnection(): Connection;

    abstract protected function transactional(\Closure $operations): void;
}
