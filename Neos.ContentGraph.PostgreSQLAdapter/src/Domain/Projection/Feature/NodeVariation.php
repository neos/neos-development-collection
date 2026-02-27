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
use Neos\ContentGraph\PostgreSQLAdapter\ContentGraphTableNames;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\HierarchyRelationRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoints;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionReadQueries;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionWriteQueries;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeGeneralizationVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodePeerVariantWasCreated;
use Neos\ContentRepository\Core\Feature\NodeVariation\Event\NodeSpecializationVariantWasCreated;
use Neos\ContentRepository\Core\Projection\ContentGraph\Timestamps;
use Neos\EventStore\Model\EventEnvelope;

/**
 * The node disabling feature set for the hypergraph projector
 *
 * @internal
 */
trait NodeVariation
{

    abstract protected function getDatabaseConnection(): Connection;
    abstract protected function getReadQueries(): ProjectionReadQueries;
    abstract protected function getWriteQueries(): ProjectionWriteQueries;
    abstract protected function getTableNames(): ContentGraphTableNames;

    /**
     * @throws \Throwable
     */
    private function whenNodeSpecializationVariantWasCreated(NodeSpecializationVariantWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $contentStreamId = $event->contentStreamId;

        // 1. Find source node
        $sourceNode = $this->getReadQueries()->findNodeRecordByOrigin(
            $contentStreamId,
            $event->sourceOrigin,
            $event->nodeAggregateId
        );
        if ($sourceNode === null) {
            throw new \RuntimeException(
                sprintf(
                    'Failed to create node specialization variant for node "%s" in sub graph %s@%s because the source node is missing',
                    $event->nodeAggregateId->value,
                    $event->sourceOrigin->toJson(),
                    $contentStreamId->value
                ),
                1716498651
            );
        }

        // 2. Copy node to new dimension space point
        $specializedNodeAnchor = $this->getWriteQueries()->insertNodeRecord(
            $this->getDatabaseConnection(),
            $sourceNode->nodeAggregateId,
            $event->specializationOrigin,
            $sourceNode->properties,
            $sourceNode->nodeTypeName,
            $sourceNode->classification,
            $sourceNode->nodeName,
            Timestamps::create(
                $eventEnvelope->recordedAt,
                self::initiatingDateTime($eventEnvelope),
                null,
                null,
            ),
        );

        // 3. Determine affected dimension space points
        $specializedDimensionSpacePointSet = $event->specializationSiblings->toDimensionSpacePointSet();

        // 4. Update ingoing hierarchy: find which anchor of this node aggregate currently
        //    covers each affected dimension and replace it with the new specialized anchor.
        //    We search by node aggregate ID (not source anchor) because a previous specialization
        //    may have already replaced the source anchor with a different variant's anchor.
        $coveredDimensionSpacePointHashes = [];
        foreach (
            $this->getReadQueries()->findIngoingHierarchyHyperrelationRecordsForNodeAggregate(
                $contentStreamId,
                $event->nodeAggregateId,
                $specializedDimensionSpacePointSet
            ) as $match
        ) {
            /** @var HierarchyRelationRecord $ingoingRelation */
            $ingoingRelation = $match['relation'];
            /** @var NodeRelationAnchorPoint $currentChildAnchor */
            $currentChildAnchor = $match['childNodeAnchor'];
            $ingoingRelation->replaceChildNodeAnchor(
                $currentChildAnchor,
                $specializedNodeAnchor,
                $this->getDatabaseConnection(),
                $this->getTableNames()
            );
            $coveredDimensionSpacePointHashes[$ingoingRelation->dimensionSpacePoint->hash] = true;
        }

        // 5. For uncovered dimensions, create new hierarchy relations
        $uncoveredDimensionSpacePoints = [];
        foreach ($specializedDimensionSpacePointSet as $dimensionSpacePoint) {
            if (!isset($coveredDimensionSpacePointHashes[$dimensionSpacePoint->hash])) {
                $uncoveredDimensionSpacePoints[] = $dimensionSpacePoint;
            }
        }
        if (!empty($uncoveredDimensionSpacePoints)) {
            $sourceParent = $this->getReadQueries()->findParentNodeRecordByOrigin(
                $contentStreamId,
                $event->sourceOrigin,
                $event->nodeAggregateId
            );
            if ($sourceParent === null) {
                throw new \RuntimeException(
                    sprintf(
                        'Failed to create node specialization variant for node "%s" in sub graph %s@%s because the source parent node is missing',
                        $event->nodeAggregateId->value,
                        $event->sourceOrigin->toJson(),
                        $contentStreamId->value
                    ),
                    1716498695
                );
            }
            foreach ($uncoveredDimensionSpacePoints as $uncoveredDimensionSpacePoint) {
                $parentNode = $this->getReadQueries()->findNodeRecordByCoverage(
                    $contentStreamId,
                    $uncoveredDimensionSpacePoint,
                    $sourceParent->nodeAggregateId
                );
                if ($parentNode === null) {
                    throw new \RuntimeException(
                        sprintf(
                            'Failed to create node specialization variant for node "%s" in sub graph %s@%s because the target parent node "%s" is missing in dimension %s',
                            $event->nodeAggregateId->value,
                            $event->sourceOrigin->toJson(),
                            $contentStreamId->value,
                            $sourceParent->nodeAggregateId->value,
                            $uncoveredDimensionSpacePoint->toJson()
                        ),
                        1716498734
                    );
                }

                $succeedingSiblingNodeAggregateId = $event->specializationSiblings
                    ->getSucceedingSiblingIdForDimensionSpacePoint($uncoveredDimensionSpacePoint);
                $succeedingSiblingAnchor = null;
                if ($succeedingSiblingNodeAggregateId !== null) {
                    $succeedingSiblingNode = $this->getReadQueries()->findNodeRecordByCoverage(
                        $contentStreamId,
                        $uncoveredDimensionSpacePoint,
                        $succeedingSiblingNodeAggregateId
                    );
                    $succeedingSiblingAnchor = $succeedingSiblingNode?->relationAnchorPoint;
                }

                $existingHierarchy = $this->getReadQueries()->findHierarchyHyperrelationRecordByParentNodeAnchor(
                    $contentStreamId,
                    $uncoveredDimensionSpacePoint,
                    $parentNode->relationAnchorPoint
                );
                if ($existingHierarchy !== null) {
                    $this->getWriteQueries()->addChildNodeAnchorBeforeSuccessor(
                        $this->getDatabaseConnection(),
                        $existingHierarchy->getDatabaseIdentifier(),
                        $specializedNodeAnchor,
                        $succeedingSiblingAnchor
                    );
                } else {
                    $this->getWriteQueries()->addHierarchyRelationRecordToDatabase(
                        $this->getDatabaseConnection(),
                        new HierarchyRelationRecord(
                            $contentStreamId,
                            $parentNode->relationAnchorPoint,
                            $uncoveredDimensionSpacePoint,
                            NodeRelationAnchorPoints::fromArray([$specializedNodeAnchor])
                        )
                    );
                }
            }
        }

        // 6. Update outgoing hierarchy: find which anchor of this node aggregate is
        //    currently the parent in each affected dimension and replace with specialized anchor
        foreach (
            $this->getReadQueries()->findOutgoingHierarchyHyperrelationRecordsForNodeAggregate(
                $contentStreamId,
                $event->nodeAggregateId,
                $specializedDimensionSpacePointSet
            ) as $match
        ) {
            /** @var HierarchyRelationRecord $outgoingRelation */
            $outgoingRelation = $match['relation'];
            $this->getWriteQueries()->replaceParentNodeAnchorOnHierarchyRecord(
                $this->getDatabaseConnection(),
                $outgoingRelation->getDatabaseIdentifier(),
                $specializedNodeAnchor
            );
        }

        // 7. Copy reference relations from the source node
        foreach (
            $this->getReadQueries()->findOutgoingReferenceHyperrelationRecords(
                $sourceNode->relationAnchorPoint
            ) as $outgoingReferenceRelation
        ) {
            $copiedReferenceRelation = $outgoingReferenceRelation->withSourceNodeAnchor($specializedNodeAnchor);
            $this->getWriteQueries()->addReferenceToDatabase($this->getDatabaseConnection(), $copiedReferenceRelation);
        }
    }

    private function whenNodeGeneralizationVariantWasCreated(NodeGeneralizationVariantWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $siblings = [];
        foreach ($event->variantSucceedingSiblings->items as $sibling) {
            $siblings[$sibling->dimensionSpacePoint->hash] = [
                'nodeaggregateid' => $sibling->nodeAggregateId?->value,
                'dimension' => $sibling->dimensionSpacePoint->coordinates
            ];
        }

        $timestamps = Timestamps::create(
            $eventEnvelope->recordedAt,
            self::initiatingDateTime($eventEnvelope),
            null,
            null,
        );

        $parameters = [
            'nodeaggregateid' => $event->nodeAggregateId->value,
            'contentstreamid' => $event->contentStreamId->value,
            'sourceorigindimensionhash' => $event->sourceOrigin->hash,
            'generalizationorigin' => $event->generalizationOrigin->toJson(),
            'generalizationoriginhash' => $event->generalizationOrigin->hash,
            'affecteddimensionsandsiblings' => json_encode($siblings),
            'created' => $timestamps->created->format('Y-m-d H:i:s'),
            'originalcreated' => $timestamps->originalCreated->format('Y-m-d H:i:s'),
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
                      nodetypename, properties, classification, nodename, created, originalcreated)
                     select sn.nodeaggregateid,
                            :generalizationorigin,
                            :generalizationoriginhash,
                            sn.nodetypename,
                            sn.properties,
                            sn.classification,
                            sn.nodename,
                            :created::timestamp,
                            :originalcreated::timestamp
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
                       g.relationanchorpoint),
                     subtreetags = CASE
                       WHEN jsonb_exists({$this->tableNames->hierarchyRelation()}.subtreetags, o.relationanchorpoint::text)
                       THEN ({$this->tableNames->hierarchyRelation()}.subtreetags - o.relationanchorpoint::text)
                            || jsonb_build_object(g.relationanchorpoint::text,
                                 {$this->tableNames->hierarchyRelation()}.subtreetags->(o.relationanchorpoint::text))
                       ELSE {$this->tableNames->hierarchyRelation()}.subtreetags
                     END
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
                 -- get the subtree tags of the source node (e.g. disabled state)
                 -- so they can be carried over to the new hierarchy relations
                 source_subtree_tags as (
                   select (
                     select h.subtreetags -> (sn.relationanchorpoint::text)
                     from {$this->tableNames->hierarchyRelation()} h
                     where sn.relationanchorpoint = any(h.childnodeanchors)
                       and h.contentstreamid = :contentstreamid
                       and h.dimensionspacepointhash = :sourceorigindimensionhash
                     limit 1
                   ) as tags
                   from source_node sn
                 ),
                 -- now add the missing hierarchy relations
                 missing_hierarchy_relations as (
                   insert into cr_default_p_graph_hierarchyrelation
                     (contentstreamid, parentnodeanchor, dimensionspacepointhash,
                      dimensionspacepoint, childnodeanchors, subtreetags)
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
                     array[gnc.relationanchorpoint],
                     CASE WHEN sst.tags IS NOT NULL
                          THEN jsonb_build_object(gnc.relationanchorpoint::text, sst.tags)
                          ELSE '{}'::jsonb
                     END
                   from missing_coverage_relationpoints mc, generalized_node_copy gnc, source_subtree_tags sst
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
                          ),
                          subtreetags = CASE
                            WHEN (select tags from source_subtree_tags) IS NOT NULL
                            THEN COALESCE({$this->tableNames->hierarchyRelation()}.subtreetags, '{}'::jsonb)
                                 || jsonb_build_object(excluded.childnodeanchors[1]::text,
                                      (select tags from source_subtree_tags))
                            ELSE {$this->tableNames->hierarchyRelation()}.subtreetags
                          END
                 )
            -- finally, copy the reference relations
            insert into {$this->tableNames->referenceRelation()}
                (sourcenodeanchor, name, position, properties, targetnodeaggregateid)
            select
                c.relationanchorpoint,
                ref.name,
                ref.position,
                ref.properties,
                ref.targetnodeaggregateid
            from {$this->tableNames->referenceRelation()} ref, source_node sn, generalized_node_copy c
            where ref.sourcenodeanchor = sn.relationanchorpoint
        SQL;

        $this->getDatabaseConnection()->executeQuery($query, $parameters);
    }

    private function whenNodePeerVariantWasCreated(NodePeerVariantWasCreated $event, EventEnvelope $eventEnvelope): void
    {
        $siblings = [];
        foreach ($event->peerSucceedingSiblings->items as $sibling) {
            $siblings[$sibling->dimensionSpacePoint->hash] = [
                'nodeaggregateid' => $sibling->nodeAggregateId?->value,
                'dimension' => $sibling->dimensionSpacePoint->coordinates
            ];
        }

        $timestamps = Timestamps::create(
            $eventEnvelope->recordedAt,
            self::initiatingDateTime($eventEnvelope),
            null,
            null,
        );

        $parameters = [
            'nodeaggregateid' => $event->nodeAggregateId->value,
            'contentstreamid' => $event->contentStreamId->value,
            'sourceorigindimensionhash' => $event->sourceOrigin->hash,
            'peerorigin' => $event->peerOrigin->toJson(),
            'peeroriginhash' => $event->peerOrigin->hash,
            'affecteddimensionsandsiblings' => json_encode($siblings),
            'created' => $timestamps->created->format('Y-m-d H:i:s'),
            'originalcreated' => $timestamps->originalCreated->format('Y-m-d H:i:s'),
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
                      nodetypename, properties, classification, nodename, created, originalcreated)
                     select sn.nodeaggregateid,
                            :peerorigin,
                            :peeroriginhash,
                            sn.nodetypename,
                            sn.properties,
                            sn.classification,
                            sn.nodename,
                            :created::timestamp,
                            :originalcreated::timestamp
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
                       p.relationanchorpoint),
                     subtreetags = CASE
                       WHEN jsonb_exists({$this->tableNames->hierarchyRelation()}.subtreetags, o.relationanchorpoint::text)
                       THEN ({$this->tableNames->hierarchyRelation()}.subtreetags - o.relationanchorpoint::text)
                            || jsonb_build_object(p.relationanchorpoint::text,
                                 {$this->tableNames->hierarchyRelation()}.subtreetags->(o.relationanchorpoint::text))
                       ELSE {$this->tableNames->hierarchyRelation()}.subtreetags
                     END
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
            -- finally, copy the reference relations
            insert into {$this->tableNames->referenceRelation()}
                (sourcenodeanchor, name, position, properties, targetnodeaggregateid)
            select
                c.relationanchorpoint,
                ref.name,
                ref.position,
                ref.properties,
                ref.targetnodeaggregateid
            from {$this->tableNames->referenceRelation()} ref, source_node sn, peer_node_copy c
            where ref.sourcenodeanchor = sn.relationanchorpoint
        SQL;

        $this->getDatabaseConnection()->executeQuery($query, $parameters);
    }

}
