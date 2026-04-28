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

                // Look up the parent's tags in this dimension so they can be inherited by the new child.
                // We query the parent's ingoing hierarchy relation to get its subtree tags,
                // then convert ALL tags (explicit + inherited) to inherited for the child.
                $tableHierarchy = $this->getTableNames()->hierarchyRelation();
                $parentTagsJson = $this->getDatabaseConnection()->fetchOne(
                    'SELECT h.subtreetags->(:nodeAnchor::text)
                     FROM ' . $tableHierarchy . ' h
                     WHERE :nodeAnchor::bigint = ANY(h.childnodeanchors)
                       AND h.contentstreamid = :contentStreamId
                       AND h.dimensionspacepointhash = :dimensionSpacePointHash',
                    [
                        'nodeAnchor' => $parentNode->relationAnchorPoint->value,
                        'contentStreamId' => $contentStreamId->value,
                        'dimensionSpacePointHash' => $uncoveredDimensionSpacePoint->hash,
                    ]
                );
                $inheritedTagsJson = '{}';
                if (is_string($parentTagsJson)) {
                    $parentTagsArray = json_decode($parentTagsJson, true, 512, JSON_THROW_ON_ERROR);
                    if (!empty($parentTagsArray)) {
                        // Convert all parent tags to inherited (null)
                        $tagObj = [];
                        foreach (array_keys($parentTagsArray) as $tagName) {
                            $tagObj[$tagName] = null;
                        }
                        $inheritedTagsJson = json_encode($tagObj, JSON_THROW_ON_ERROR);
                    }
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

                // Set inherited subtree tags from parent on the new child's hierarchy entry
                if ($inheritedTagsJson !== '{}') {
                    $tableName = $this->getTableNames()->hierarchyRelation();
                    $this->getDatabaseConnection()->executeStatement(
                        <<<SQL
                            UPDATE {$tableName}
                            SET subtreetags = COALESCE(subtreetags, '{}'::jsonb)
                                || jsonb_build_object(:childAnchorText::text, :inheritedTags::jsonb)
                            WHERE contentstreamid = :contentstreamid
                              AND parentnodeanchor = :parentnodeanchor
                              AND dimensionspacepointhash = :dimensionspacepointhash
                        SQL,
                        [
                            'childAnchorText' => (string)$specializedNodeAnchor->value,
                            'inheritedTags' => $inheritedTagsJson,
                            'contentstreamid' => $contentStreamId->value,
                            'parentnodeanchor' => $parentNode->relationAnchorPoint->value,
                            'dimensionspacepointhash' => $uncoveredDimensionSpacePoint->hash,
                        ]
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
                                 from {$this->tableNames->functionFindNodeByOrigin()}(
                                   :nodeaggregateid,
                                   :contentstreamid,
                                   :sourceorigindimensionhash
                                      )),
                 -- perform the copy to generalized dimension
                 generalized_node_copy as (
                   insert into {$this->tableNames->node()}
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
                 -- get only the EXPLICIT subtree tags of the source node (e.g. disabled state)
                 -- so they can be carried over to the new hierarchy relations.
                 -- Inherited tags (null) are NOT copied because they depend on the
                 -- parent's tags in the TARGET dimension, not the source dimension.
                 source_subtree_tags as (
                   select (
                     select coalesce(
                       (select jsonb_object_agg(key, value)
                        from jsonb_each(h.subtreetags -> (sn.relationanchorpoint::text))
                        where value = 'true'::jsonb),
                       null
                     )
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
                   insert into {$this->tableNames->hierarchyRelation()}
                     (contentstreamid, parentnodeanchor, dimensionspacepointhash,
                      dimensionspacepoint, childnodeanchors, subtreetags)
                   select
                     :contentstreamid,
                     pa.anchor,
                     mc.specializeddimensionhash,
                     mc.dimensionspacepoint,
                     array[gnc.relationanchorpoint],
                     jsonb_build_object(
                       gnc.relationanchorpoint::text,
                       -- Effective tags: parent's explicit tags in target dim (as inherited/null)
                       -- merged with source's explicit tags (true). The || operator
                       -- lets source explicit tags take precedence over parent inherited.
                       coalesce(
                         (select jsonb_object_agg(key, null)
                          from jsonb_each(coalesce(
                            (select h_pt.subtreetags -> (pa.anchor::text)
                             from {$this->tableNames->hierarchyRelation()} h_pt
                             where pa.anchor = any(h_pt.childnodeanchors)
                               and h_pt.contentstreamid = :contentstreamid
                               and h_pt.dimensionspacepointhash = mc.specializeddimensionhash
                             limit 1),
                            '{}'::jsonb))
                          where value = 'true'::jsonb),
                         '{}'::jsonb
                       )
                       || coalesce(sst.tags, '{}'::jsonb)
                     )
                   from missing_coverage_relationpoints mc,
                        generalized_node_copy gnc,
                        source_subtree_tags sst,
                        LATERAL (
                          select {$this->tableNames->functionGetParentRelationAnchorPointInDimension()}(
                            :nodeaggregateid,
                            :contentstreamid,
                            :sourceorigindimensionhash,
                            mc.specializeddimensionhash
                          ) as anchor
                        ) pa
                   on conflict on constraint {$this->tableNames->hierarchyRelation()}_pkey
                     do update
                          set childnodeanchors = insert_into_array_before_successor(
                            {$this->tableNames->hierarchyRelation()}.childnodeanchors,
                            excluded.childnodeanchors[1],
                            (select {$this->tableNames->functionGetRelationAnchorPoint()}(
                                ad.siblingnodeaggregateid,
                                :contentstreamid,
                                excluded.dimensionspacepointhash
                                ) from affected_dimensions ad
                             where ad.specializeddimensionhash = excluded.dimensionspacepointhash)
                          ),
                          subtreetags = COALESCE({$this->tableNames->hierarchyRelation()}.subtreetags, '{}'::jsonb)
                            || jsonb_build_object(
                                 excluded.childnodeanchors[1]::text,
                                 coalesce(
                                   (select jsonb_object_agg(key, null)
                                    from jsonb_each(coalesce(
                                      (select h_pt2.subtreetags -> ({$this->tableNames->hierarchyRelation()}.parentnodeanchor::text)
                                       from {$this->tableNames->hierarchyRelation()} h_pt2
                                       where {$this->tableNames->hierarchyRelation()}.parentnodeanchor = any(h_pt2.childnodeanchors)
                                         and h_pt2.contentstreamid = :contentstreamid
                                         and h_pt2.dimensionspacepointhash = excluded.dimensionspacepointhash
                                       limit 1),
                                      '{}'::jsonb))
                                    where value = 'true'::jsonb),
                                   '{}'::jsonb
                                 )
                                 || coalesce((select tags from source_subtree_tags), '{}'::jsonb)
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
                                 from {$this->tableNames->functionFindNodeByOrigin()}(
                                   :nodeaggregateid,
                                   :contentstreamid,
                                   :sourceorigindimensionhash
                                      )),
                 -- perform the copy
                 peer_node_copy as (
                   insert into {$this->tableNames->node()}
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
                 -- For peer variants, only parent tags are inherited (no source tags),
                 -- matching DoctrineDBAL's connectHierarchy behavior.
                 missing_hierarchy_relations as (
                   insert into {$this->tableNames->hierarchyRelation()}
                     (contentstreamid, parentnodeanchor, dimensionspacepointhash,
                      dimensionspacepoint, childnodeanchors, subtreetags)
                   select
                     :contentstreamid,
                     pa.anchor,
                     mc.specializeddimensionhash,
                     mc.dimensionspacepoint,
                     array[pnc.relationanchorpoint],
                     jsonb_build_object(
                       pnc.relationanchorpoint::text,
                       -- Only inherit parent's ALL tags (explicit+inherited) as inherited (null)
                       coalesce(
                         (select jsonb_object_agg(key, null)
                          from jsonb_each(coalesce(
                            (select h_pt.subtreetags -> (pa.anchor::text)
                             from {$this->tableNames->hierarchyRelation()} h_pt
                             where pa.anchor = any(h_pt.childnodeanchors)
                               and h_pt.contentstreamid = :contentstreamid
                               and h_pt.dimensionspacepointhash = mc.specializeddimensionhash
                             limit 1),
                            '{}'::jsonb))
                         ),
                         '{}'::jsonb
                       )
                     )
                   from missing_coverage_relationpoints mc,
                        peer_node_copy pnc,
                        LATERAL (
                          select {$this->tableNames->functionGetParentRelationAnchorPointInDimension()}(
                            :nodeaggregateid,
                            :contentstreamid,
                            :sourceorigindimensionhash,
                            mc.specializeddimensionhash
                          ) as anchor
                        ) pa
                   on conflict on constraint {$this->tableNames->hierarchyRelation()}_pkey
                     do update
                          set childnodeanchors = insert_into_array_before_successor(
                            {$this->tableNames->hierarchyRelation()}.childnodeanchors,
                            excluded.childnodeanchors[1],
                            (select {$this->tableNames->functionGetRelationAnchorPoint()}(
                                ad.siblingnodeaggregateid,
                                :contentstreamid,
                                excluded.dimensionspacepointhash
                                ) from affected_dimensions ad
                             where ad.specializeddimensionhash = excluded.dimensionspacepointhash)
                          ),
                          subtreetags = COALESCE({$this->tableNames->hierarchyRelation()}.subtreetags, '{}'::jsonb)
                            || jsonb_build_object(
                                 excluded.childnodeanchors[1]::text,
                                 coalesce(
                                   (select jsonb_object_agg(key, null)
                                    from jsonb_each(coalesce(
                                      (select h_pt2.subtreetags -> ({$this->tableNames->hierarchyRelation()}.parentnodeanchor::text)
                                       from {$this->tableNames->hierarchyRelation()} h_pt2
                                       where {$this->tableNames->hierarchyRelation()}.parentnodeanchor = any(h_pt2.childnodeanchors)
                                         and h_pt2.contentstreamid = :contentstreamid
                                         and h_pt2.dimensionspacepointhash = excluded.dimensionspacepointhash
                                       limit 1),
                                      '{}'::jsonb))
                                   ),
                                   '{}'::jsonb
                                 )
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
