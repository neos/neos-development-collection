<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Types;
use Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\NodeRecord;
use Neos\ContentRepository\Core\DimensionSpace\DimensionSpacePointSet;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Event\NodeAggregateWasDisabled;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\EventEnvelope;

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
    private function whenNodeAggregateWasDisabled(NodeAggregateWasDisabled $event): void
    {
        $this->transactional(function () use ($event) {
            $this->getDatabaseConnection()->executeStatement(
                '
-- GraphProjector::whenNodeAggregateWasDisabled
insert into ' . $this->getTableNamePrefix() . '_restrictionrelation
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
        });
    }

    abstract protected function getDatabaseConnection(): Connection;

    abstract protected function transactional(\Closure $operations): void;
}
