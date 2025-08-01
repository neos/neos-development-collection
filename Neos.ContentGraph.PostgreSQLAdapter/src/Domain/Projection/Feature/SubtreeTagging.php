<?php

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\Feature;

use Doctrine\DBAL\Connection;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionReadQueries;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\RestrictionHyperrelationRecord;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasTagged;
use Neos\ContentRepository\Core\Feature\SubtreeTagging\Event\SubtreeWasUntagged;

/**
 * The subtree tagging feature set for the hypergraph projector
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
        $parameters = [
            'nodeaggregateid' => $event->nodeAggregateId->value,
            'contentstreamid' => $event->contentStreamId->value,
            'affecteddimensionspacepointhashes' => $event->affectedDimensionSpacePoints->getPointHashes(),
            'subtreetag' => $event->tag->value
        ];

        $parameterTypes = [
            'affecteddimensionspacepointhashes' => Connection::PARAM_STR_ARRAY
        ];

        $query = <<<SQL
            with all_subtree_nodes as (
                    -- TODO I think, the "other way around" would be better, ask for affected nodes later
                    --      since we only need the affected nodes in the INSERT case (not in the UPDATE case)
                    with recursive descendant_nodes as (
                        -- --------------------------------
                        -- INITIAL query: select the root nodes
                        -- --------------------------------
                        select
                           n.nodeaggregateid,
                           n.relationanchorpoint,
                           h.dimensionspacepointhash
                        from {$this->tableNames->node()} n
                        inner join {$this->tableNames->hierarchyRelation()} h
                            on n.relationanchorpoint = any(h.childnodeanchors)
                        where n.nodeaggregateid = :nodeaggregateid
                            and h.contentstreamid = :contentstreamid
                            and h.dimensionspacepointhash in (:affecteddimensionspacepointhashes)
                        union all
                        -- --------------------------------
                        -- RECURSIVE query: do one "child" query step
                        -- --------------------------------
                        select
                            c.nodeaggregateid,
                            c.relationanchorpoint,
                            h.dimensionspacepointhash
                        from
                            descendant_nodes p
                        inner join {$this->tableNames->hierarchyRelation()} h
                            on h.parentnodeanchor = p.relationanchorpoint
                        inner join {$this->tableNames->node()} c
                            on c.relationanchorpoint = any(h.childnodeanchors)
                        where
                            h.contentstreamid = :contentstreamid
                            and h.dimensionspacepointhash in (:affecteddimensionspacepointhashes)
                    )
                    select
                        dn.nodeaggregateid,
                        dn.dimensionspacepointhash
                    from descendant_nodes dn
                ),
                grouped_by_variant as (
                    select subt.dimensionspacepointhash,
                        array_agg(subt.nodeaggregateid) as affectednodeaggregateids
                    from all_subtree_nodes subt
                    group by subt.dimensionspacepointhash
                )
            insert into {$this->tableNames->subTreeTagsRelation()}
                (contentstreamid, dimensionspacepointhash, originnodeaggregateid,
                 affectednodeaggregateids, subtreetags)
            select
                :contentstreamid,
                vg.dimensionspacepointhash,
                :nodeaggregateid,
                vg.affectednodeaggregateids,
                array[:subtreetag]::varchar(36)[]
            from grouped_by_variant vg
            on conflict on constraint cr_default_p_graph_subtreetags_pkey
                do update
                    set subtreetags = array(select distinct unnest({$this->tableNames->subTreeTagsRelation()}.subtreetags || :subtreetag::varchar(36)))
        SQL;
        $this->getDatabaseConnection()->executeQuery($query, $parameters, $parameterTypes);
    }

    // ARRAY(SELECT DISTINCT UNNEST(arr_str || '{a,b,c}'))
    /**
     * @throws \Throwable
     */
    private function whenSubtreeWasUntagged(SubtreeWasUntagged $event): void
    {
        $restrictionRelations = $this->getReadQueries()->findOutgoingRestrictionRelations(
            $event->contentStreamId,
            $event->affectedDimensionSpacePoints,
            $event->nodeAggregateId,
        );
        foreach ($restrictionRelations as $restrictionRelation) {
            $restrictionRelation->removeFromDatabase($this->getDatabaseConnection(), $this->tableNamePrefix);
        }
    }

    abstract protected function getReadQueries(): ProjectionReadQueries;

    abstract protected function getDatabaseConnection(): Connection;
}
