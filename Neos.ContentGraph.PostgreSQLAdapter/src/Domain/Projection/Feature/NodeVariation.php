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
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;

/**
 * The node disabling feature set for the hypergraph projector
 *
 * @internal
 */
trait NodeVariation
{

    abstract protected function getDatabaseConnection(): Connection;

    /**
     * @throws \Throwable
     */
    private function whenNodeSpecializationVariantWasCreated(NodeSpecializationVariantWasCreated $event): void
    {
        $siblings = [];
        foreach ($event->specializationSiblings->items as $sibling) {
            $siblings[$sibling->dimensionSpacePoint->hash] = [
                'nodeaggregateid' => $sibling->nodeAggregateId?->value,
                'dimension' => $sibling->dimensionSpacePoint->coordinates
            ];
        }

        $parameters = [
            'contentstreamid' => $event->contentStreamId->value,
            'nodeaggregateid' => $event->nodeAggregateId->value,
            'origindimensionspacepointhash' => $event->sourceOrigin->hash,
            'specializationorigin' => $event->specializationOrigin->toJson(),
            'specializationoriginhash' => $event->specializationOrigin->hash,
            'specializeddimensionsandsiblings' => json_encode($siblings)
        ];

        $query = <<<SQL
                    with
                        specialized_dimensions as (select
                                                         adim.specializeddimensionhash as specializeddimensionhash,
                                                         (adim.sibling ->> 'nodeaggregateid')::varchar(64) as siblingnodeaggregateid,
                                                         adim.sibling -> 'dimension' as dimensionspacepoint
                                                     from jsonb_each(:specializeddimensionsandsiblings) adim(specializeddimensionhash, sibling)),
                        -- we need the source node, to copy its values
                        source_node as (select *
                                         from {$this->tableNames->functionFindNodeByOrigin()}(
                                           :nodeaggregateid,
                                           :contentstreamid,
                                           :origindimensionspacepointhash
                                              )
                        ),
                        -- create the specialized copy and keep the auto-incremented ID
                        specialized_node_copy as (
                            insert into {$this->tableNames->node()}
                              (nodeaggregateid, origindimensionspacepoint, origindimensionspacepointhash,
                               nodetypename, properties, classification, nodename)
                            select sn.nodeaggregateid,
                                   :specializationorigin,
                                   :specializationoriginhash,
                                   sn.nodetypename,
                                   sn.properties,
                                   sn.classification,
                                   sn.nodename
                            from source_node sn
                            returning relationanchorpoint
                        ),
                        -- we need the old covering node to decide how to connect the hierarchy
                        old_covering_node as (select relationanchorpoint
                                              from {$this->tableNames->functionFindNodeByCoverage()}(
                                                :nodeaggregateid,
                                                :contentstreamid,
                                                :specializationoriginhash
                                                   )),
                        -- ### CASE 1 - an old covering node exists - replace hierarchy
                        -- Replace the old covering node with the specialized variant in
                        -- all hierarchy records (child and parent references).
                        update_ingoing_hierarchy as (
                          update {$this->tableNames->hierarchyRelation()}
                            set childnodeanchors = array_replace(
                              {$this->tableNames->hierarchyRelation()}.childnodeanchors,
                              o.relationanchorpoint,
                              s.relationanchorpoint)
                            from old_covering_node o, specialized_node_copy s
                            where o.relationanchorpoint = any (childnodeanchors)
                              -- only affected dimensions
                              and exists(select 1 from specialized_dimensions d
                                         where {$this->tableNames->hierarchyRelation()}.dimensionspacepointhash = d.specializeddimensionhash)
                              -- only if there is an old covering node
                              and o.relationanchorpoint is not null
                            returning {$this->tableNames->hierarchyRelation()}.dimensionspacepointhash
                        ),
                        update_outgoing_hierarchy as (
                          update {$this->tableNames->hierarchyRelation()}
                            set parentnodeanchor = s.relationanchorpoint
                            from old_covering_node o, specialized_node_copy s
                            where parentnodeanchor = o.relationanchorpoint
                              -- only affected dimensions
                              and exists(select 1 from specialized_dimensions d
                                         where {$this->tableNames->hierarchyRelation()}.dimensionspacepointhash = d.specializeddimensionhash)
                              -- only if there is an old covering node
                              and o.relationanchorpoint is not null),
                        -- ### CASE 2 - an old covering node does not exist - create hierarchy
                        -- Add the specialized node as child to each relation entry of all dimensions
                        -- of the parent node aggregate.
                        missing_coverage_relationpoints as (
                           select
                             ad.specializeddimensionhash as specializeddimensionhash,
                             ad.dimensionspacepoint as dimensionspacepoint
                           from specialized_dimensions ad
                           where not exists(select 1 from update_ingoing_hierarchy ui
                                            where ad.specializeddimensionhash = ui.dimensionspacepointhash)
                         ),
                        missing_hierarchy_relations as (
                          insert into cr_default_p_graph_hierarchyrelation
                            (contentstreamid, parentnodeanchor, dimensionspacepointhash,
                             dimensionspacepoint, childnodeanchors)
                          select
                            :contentstreamid,
                            neoscr_default_get_parent_relationanchorpoint_in_dim(
                              :nodeaggregateid,
                              :contentstreamid,
                              :origindimensionspacepointhash,
                              mc.specializeddimensionhash
                            ),
                            mc.specializeddimensionhash,
                            mc.dimensionspacepoint,
                            array[snc.relationanchorpoint]
                          from missing_coverage_relationpoints mc, specialized_node_copy snc
                          on conflict on constraint cr_default_p_graph_hierarchyrelation_pkey
                            do update
                                 set childnodeanchors = insert_into_array_before_successor(
                                   cr_default_p_graph_hierarchyrelation.childnodeanchors,
                                   excluded.childnodeanchors[1],
                                   (select neoscr_default_get_relationanchorpoint(
                                       ad.siblingnodeaggregateid,
                                       :contentstreamid,
                                       excluded.dimensionspacepointhash
                                       ) from specialized_dimensions ad
                                    where ad.specializeddimensionhash = excluded.dimensionspacepointhash)
                                 )
                        )
                    select 1
        SQL;

        $this->getDatabaseConnection()->executeQuery($query, $parameters);
        /**
         * TODO references
         * $this->copyReferenceRelations(
         * $sourceNode->relationAnchorPoint,
         * $specializedNode->relationAnchorPoint
         * );
         */
    }

    private function whenNodeGeneralizationVariantWasCreated(NodeGeneralizationVariantWasCreated $event): void
    {
        $siblings = [];
        foreach ($event->variantSucceedingSiblings->items as $sibling) {
            $siblings[$sibling->dimensionSpacePoint->hash] = [
                'nodeaggregateid' => $sibling->nodeAggregateId?->value,
                'dimension' => $sibling->dimensionSpacePoint->coordinates
            ];
        }

        $parameters = [
            'nodeaggregateid' => $event->nodeAggregateId->value,
            'contentstreamid' => $event->contentStreamId->value,
            'sourceorigindimensionhash' => $event->sourceOrigin->hash,
            'generalizationorigin' => $event->generalizationOrigin->toJson(),
            'generalizationoriginhash' => $event->generalizationOrigin->hash,
            'affecteddimensionsandsiblings' => json_encode($siblings)
        ];

        $query = <<<SQL
            with affected_dimensions as (select
                                             adim.specializeddimensionhash as specializeddimensionhash,
                                             (adim.sibling ->> 'nodeaggregateid')::varchar(64) as siblingnodeaggregateid,
                                             adim.sibling -> 'dimension' as dimensionspacepoint
                                         from jsonb_each(:affecteddimensionsandsiblings) adim(specializeddimensionhash, sibling)),
                 -- get source node for copy operation
                 source_node as (select *
                                 from neoscr_default_find_node_by_origin(
                                   :nodeaggregateid,
                                   :contentstreamid,
                                   :sourceorigindimensionhash
                                      )),
                 -- perform the copy to generalized dimension
                 generalized_node_copy as (
                   insert into cr_default_p_graph_node
                     (nodeaggregateid, origindimensionspacepoint, origindimensionspacepointhash,
                      nodetypename, properties, classification, nodename)
                     select sn.nodeaggregateid,
                            :generalizationorigin,
                            :generalizationoriginhash,
                            sn.nodetypename,
                            sn.properties,
                            sn.classification,
                            sn.nodename
                   from source_node sn
                   returning *),
                 old_ingoing_hierarchy as (
                    select
                      oih.relationanchorpoint,
                      oih.parentnodeanchor,
                      oih.dimensionspacepointhash,
                      oih.contentstream as contentstreamid
                    from {$this->tableNames->functionFindIngoingHierarchy()}(
                        :nodeaggregateid,
                        :contentstreamid,
                        (select array_agg(ad.specializeddimensionhash) from affected_dimensions ad)
                    ) oih
                 ),
                 update_ingoing_hierarchy as (
                   update {$this->tableNames->hierarchyRelation()}
                     set childnodeanchors = array_replace(
                       {$this->tableNames->hierarchyRelation()}.childnodeanchors,
                       o.relationanchorpoint,
                       g.relationanchorpoint)
                     from old_ingoing_hierarchy o, generalized_node_copy g
                     where {$this->tableNames->hierarchyRelation()}.parentnodeanchor = o.parentnodeanchor
                       and {$this->tableNames->hierarchyRelation()}.contentstreamid = o.contentstreamid
                       -- only affected dimensions
                       and {$this->tableNames->hierarchyRelation()}.dimensionspacepointhash = o.dimensionspacepointhash
                       -- only if there is an old covering node
                       and o.relationanchorpoint is not null
                     returning {$this->tableNames->hierarchyRelation()}.dimensionspacepointhash
                 ),
                 -- ### update outgoing hierarhcy
                 old_outgoing_hierarchy as (
                    select
                      ooh.relationanchorpoint,
                      ooh.parentnodeanchor,
                      ooh.dimensionspacepointhash,
                      ooh.contentstream as contentstreamid
                    from {$this->tableNames->functionFindOutgoingHierarchy()}(
                        :nodeaggregateid,
                        :contentstreamid,
                        (select array_agg(ad.specializeddimensionhash) from affected_dimensions ad)
                    ) ooh
                 ),
                 update_outgoing_hierarchy as (
                   update {$this->tableNames->hierarchyRelation()}
                     set parentnodeanchor = g.relationanchorpoint
                     from old_outgoing_hierarchy o, generalized_node_copy g
                     where {$this->tableNames->hierarchyRelation()}.parentnodeanchor = o.parentnodeanchor
                       and {$this->tableNames->hierarchyRelation()}.contentstreamid = o.contentstreamid
                       -- only affected dimensions
                       and {$this->tableNames->hierarchyRelation()}.dimensionspacepointhash = o.dimensionspacepointhash
                       -- only if there is an old covering node
                       and o.relationanchorpoint is not null
                 ),
                 missing_coverage_relationpoints as (
                   select
                     ad.specializeddimensionhash as specializeddimensionhash,
                     ad.dimensionspacepoint as dimensionspacepoint
                   from affected_dimensions ad
                   where not exists(select 1 from update_ingoing_hierarchy cs where ad.specializeddimensionhash = cs.dimensionspacepointhash)
                 ),
                 -- now add the missing hierarchy relations
                 missing_hierarchy_relations as (
                   insert into cr_default_p_graph_hierarchyrelation
                     (contentstreamid, parentnodeanchor, dimensionspacepointhash,
                      dimensionspacepoint, childnodeanchors)
                   select
                     :contentstreamid,
                     neoscr_default_get_parent_relationanchorpoint_in_dim(
                       :nodeaggregateid,
                       :contentstreamid,
                       :sourceorigindimensionhash,
                       mc.specializeddimensionhash
                     ),
                     mc.specializeddimensionhash,
                     mc.dimensionspacepoint,
                     array[gnc.relationanchorpoint]
                   from missing_coverage_relationpoints mc, generalized_node_copy gnc
                   on conflict on constraint cr_default_p_graph_hierarchyrelation_pkey
                     do update
                          set childnodeanchors = insert_into_array_before_successor(
                            cr_default_p_graph_hierarchyrelation.childnodeanchors,
                            excluded.childnodeanchors[1],
                            (select neoscr_default_get_relationanchorpoint(
                                ad.siblingnodeaggregateid,
                                :contentstreamid,
                                excluded.dimensionspacepointhash
                                ) from affected_dimensions ad
                             where ad.specializeddimensionhash = excluded.dimensionspacepointhash)
                          )
                 )
            -- TODO copy reference relations
            select 1
        SQL;

        $this->getDatabaseConnection()->executeQuery($query, $parameters);

        /* TODO
        $this->copyReferenceRelations(
            $sourceNode->relationAnchorPoint,
            $generalizedNode->relationAnchorPoint
        );
        */
    }

    private function whenNodePeerVariantWasCreated(NodePeerVariantWasCreated $event): void
    {
        $siblings = [];
        foreach ($event->peerSucceedingSiblings->items as $sibling) {
            $siblings[$sibling->dimensionSpacePoint->hash] = [
                'nodeaggregateid' => $sibling->nodeAggregateId?->value,
                'dimension' => $sibling->dimensionSpacePoint->coordinates
            ];
        }

        // TODO timestamps from event envelope

        $parameters = [
            'nodeaggregateid' => $event->nodeAggregateId->value,
            'contentstreamid' => $event->contentStreamId->value,
            'sourceorigindimensionhash' => $event->sourceOrigin->hash,
            'peerorigin' => $event->peerOrigin->toJson(),
            'peeroriginhash' => $event->peerOrigin->hash,
            'affecteddimensionsandsiblings' => json_encode($siblings)
        ];

        $query = <<<SQL
            with affected_dimensions as (select
                                             adim.specializeddimensionhash as specializeddimensionhash,
                                             (adim.sibling ->> 'nodeaggregateid')::varchar(64) as siblingnodeaggregateid,
                                             adim.sibling -> 'dimension' as dimensionspacepoint
                                         from jsonb_each(:affecteddimensionsandsiblings) adim(specializeddimensionhash, sibling)),
                 -- get source node for copy operation
                 source_node as (select *
                                 from neoscr_default_find_node_by_origin(
                                   :nodeaggregateid,
                                   :contentstreamid,
                                   :sourceorigindimensionhash
                                      )),
                 -- perform the copy
                 peer_node_copy as (
                   insert into cr_default_p_graph_node
                     (nodeaggregateid, origindimensionspacepoint, origindimensionspacepointhash,
                      nodetypename, properties, classification, nodename)
                     select sn.nodeaggregateid,
                            :peerorigin,
                            :peeroriginhash,
                            sn.nodetypename,
                            sn.properties,
                            sn.classification,
                            sn.nodename
                   from source_node sn
                   returning *),
                 -- ### TODO comment update ingoing hierarchy
                 -- Replace the old covering node with the peer variant in
                 -- all hierarchy records (child references).
                 old_ingoing_hierarchy as (
                    select
                      oih.relationanchorpoint,
                      oih.parentnodeanchor,
                      oih.dimensionspacepointhash,
                      oih.contentstream as contentstreamid
                    from {$this->tableNames->functionFindIngoingHierarchy()}(
                        :nodeaggregateid,
                        :contentstreamid,
                        (select array_agg(ad.specializeddimensionhash) from affected_dimensions ad)
                    ) oih
                 ),
                 update_ingoing_hierarchy as (
                   update {$this->tableNames->hierarchyRelation()}
                     set childnodeanchors = array_replace(
                       {$this->tableNames->hierarchyRelation()}.childnodeanchors,
                       o.relationanchorpoint,
                       p.relationanchorpoint)
                     from old_ingoing_hierarchy o, peer_node_copy p
                     where {$this->tableNames->hierarchyRelation()}.parentnodeanchor = o.parentnodeanchor
                       and {$this->tableNames->hierarchyRelation()}.contentstreamid = o.contentstreamid
                       -- only affected dimensions
                       and {$this->tableNames->hierarchyRelation()}.dimensionspacepointhash = o.dimensionspacepointhash
                       -- only if there is an old covering node
                       and o.relationanchorpoint is not null
                     returning {$this->tableNames->hierarchyRelation()}.dimensionspacepointhash
                 ),
                 -- ### update outgoing hierarhcy
                 old_outgoing_hierarchy as (
                    select
                      ooh.relationanchorpoint,
                      ooh.parentnodeanchor,
                      ooh.dimensionspacepointhash,
                      ooh.contentstream as contentstreamid
                    from {$this->tableNames->functionFindOutgoingHierarchy()}(
                        :nodeaggregateid,
                        :contentstreamid,
                        (select array_agg(ad.specializeddimensionhash) from affected_dimensions ad)
                    ) ooh
                 ),
                 update_outgoing_hierarchy as (
                   update {$this->tableNames->hierarchyRelation()}
                     set parentnodeanchor = p.relationanchorpoint
                     from old_outgoing_hierarchy o, peer_node_copy p
                     where {$this->tableNames->hierarchyRelation()}.parentnodeanchor = o.parentnodeanchor
                       and {$this->tableNames->hierarchyRelation()}.contentstreamid = o.contentstreamid
                       -- only affected dimensions
                       and {$this->tableNames->hierarchyRelation()}.dimensionspacepointhash = o.dimensionspacepointhash
                       -- only if there is an old covering node
                       and o.relationanchorpoint is not null
                 ),
                -- ### connect parents
                missing_coverage_relationpoints as (
                   select
                     ad.specializeddimensionhash as specializeddimensionhash,
                     ad.dimensionspacepoint as dimensionspacepoint
                   from affected_dimensions ad
                   where not exists(select 1 from update_ingoing_hierarchy ui
                                    where ad.specializeddimensionhash = ui.dimensionspacepointhash)
                 ),
                 -- now add the missing hierarchy relations
                 missing_hierarchy_relations as (
                   insert into cr_default_p_graph_hierarchyrelation
                     (contentstreamid, parentnodeanchor, dimensionspacepointhash,
                      dimensionspacepoint, childnodeanchors)
                   select
                     :contentstreamid,
                     {$this->tableNames->functionGetParentRelationAnchorPointInDimension()}(
                        :nodeaggregateid,
                        :contentstreamid,
                        :sourceorigindimensionhash,
                        mc.specializeddimensionhash
                     ),
                     mc.specializeddimensionhash,
                     mc.dimensionspacepoint,
                     array[pnc.relationanchorpoint]
                   from missing_coverage_relationpoints mc, peer_node_copy pnc
                   on conflict on constraint cr_default_p_graph_hierarchyrelation_pkey
                     do update
                          set childnodeanchors = insert_into_array_before_successor(
                            cr_default_p_graph_hierarchyrelation.childnodeanchors,
                            excluded.childnodeanchors[1],
                            (select neoscr_default_get_relationanchorpoint(
                                ad.siblingnodeaggregateid,
                                :contentstreamid,
                                excluded.dimensionspacepointhash
                                ) from affected_dimensions ad
                             where ad.specializeddimensionhash = excluded.dimensionspacepointhash)
                          )
                 )
            select 1
        SQL;

        $this->getDatabaseConnection()->executeQuery($query, $parameters);

        /* TODO
        $this->copyReferenceRelations(
            $sourceNode->relationAnchorPoint,
            $generalizedNode->relationAnchorPoint
        );
        */
    }

    /*
    protected function copyReferenceRelations(
        NodeRelationAnchorPoint $sourceRelationAnchorPoint,
        NodeRelationAnchorPoint $newSourceRelationAnchorPoint
    ): void {
        // we don't care whether the target node aggregate covers the variant's origin
        // since if it doesn't, it already didn't match the source's coverage before

        $this->getDatabaseConnection()->executeStatement(
            '
                INSERT INTO ' . $this->tableNamePrefix . '_referencerelation (
                  sourcenodeanchor,
                  name,
                  position,
                  properties,
                  targetnodeaggregateid
                )
                SELECT
                  :newSourceRelationAnchorPoint AS sourcenodeanchor,
                  ref.name,
                  ref.position,
                  ref.properties,
                  ref.targetnodeaggregateid
                FROM
                    ' . $this->tableNamePrefix . '_referencerelation ref
                    WHERE ref.sourcenodeanchor = :sourceNodeAnchorPoint
            ',
            [
                'sourceNodeAnchorPoint' => $sourceRelationAnchorPoint->value,
                'newSourceRelationAnchorPoint' => $newSourceRelationAnchorPoint->value
            ]
        );
    }
    */
}
