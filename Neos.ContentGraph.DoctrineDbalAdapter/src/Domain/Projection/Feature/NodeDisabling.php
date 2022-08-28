<?php

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Feature\NodeDisabling\Event\NodeAggregateWasDisabled;

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
            // TODO: still unsure why we need an "INSERT IGNORE" here;
            // normal "INSERT" can trigger a duplicate key constraint exception
            $this->getDatabaseConnection()->executeStatement(
                '
-- GraphProjector::whenNodeAggregateWasDisabled
insert ignore into ' . $this->getTableNamePrefix() . '_restrictionrelation
(
    -- we build a recursive tree
    with recursive tree as (
         -- --------------------------------
         -- INITIAL query: select the root nodes of the tree; as given in $menuLevelNodeIdentifiers
         -- --------------------------------
         select
            n.relationanchorpoint,
            n.nodeaggregateidentifier,
            h.dimensionspacepointhash
         from
            ' . $this->getTableNamePrefix() . '_node n
         -- we need to join with the hierarchy relation, because we need the dimensionspacepointhash.
         inner join ' . $this->getTableNamePrefix() . '_hierarchyrelation h
            on h.childnodeanchor = n.relationanchorpoint
         where
            n.nodeaggregateidentifier = :entryNodeAggregateIdentifier
            and h.contentstreamidentifier = :contentStreamIdentifier
            and h.dimensionspacepointhash in (:dimensionSpacePointHashes)
    union
         -- --------------------------------
         -- RECURSIVE query: do one "child" query step
         -- --------------------------------
         select
            c.relationanchorpoint,
            c.nodeaggregateidentifier,
            h.dimensionspacepointhash
         from
            tree p
         inner join ' . $this->getTableNamePrefix() . '_hierarchyrelation h
            on h.parentnodeanchor = p.relationanchorpoint
         inner join ' . $this->getTableNamePrefix() . '_node c
            on h.childnodeanchor = c.relationanchorpoint
         where
            h.contentstreamidentifier = :contentStreamIdentifier
            and h.dimensionspacepointhash in (:dimensionSpacePointHashes)
    )

    select
        "' . $event->contentStreamIdentifier . '" as contentstreamidentifier,
        dimensionspacepointhash,
        "' . $event->nodeAggregateIdentifier . '" as originnodeaggregateidentifier,
        nodeaggregateidentifier as affectednodeaggregateidentifier
    from tree
)
            ',
                [
                    'entryNodeAggregateIdentifier' => (string)$event->nodeAggregateIdentifier,
                    'contentStreamIdentifier' => (string)$event->contentStreamIdentifier,
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
