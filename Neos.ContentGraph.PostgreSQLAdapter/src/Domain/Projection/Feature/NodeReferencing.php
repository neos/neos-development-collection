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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\EventCouldNotBeAppliedToContentGraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionReadQueries;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionWriteQueries;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ReferenceRelationRecord;
use Neos\ContentRepository\Core\Feature\NodeReferencing\Event\NodeReferencesWereSet;

/**
 * The node referencing feature set for the hypergraph projector
 *
 * @internal
 */
trait NodeReferencing
{
    use CopyOnWrite;

    /**
     * @throws \Throwable
     */
    private function whenNodeReferencesWereSet(NodeReferencesWereSet $event): void
    {
        $references = [];
        foreach ($event->references as $reference) {
            $referencesForProperty = [];
            foreach ($reference->references as $referenceForProperty) {
                $referencesForProperty[] = [
                    "target" => $referenceForProperty->targetNodeAggregateId->value,
                    "properties" => $referenceForProperty->properties->jsonSerialize()
                ];
            }
            $references[] = [
                "referenceName" => $reference->referenceName,
                "references" => $referencesForProperty
            ];
        }

        $parameters = [
            'contentstreamid' => $event->contentStreamId->value,
            'nodeaggregateid' => $event->nodeAggregateId->value,
            'affectedsourcedimensionspacepoints' => json_encode($event->affectedSourceOriginDimensionSpacePoints->getPointHashes()),
            'referencesinput' => json_encode($references)
        ];

        $query = <<<SQL
            -- FIXME the whole copy on write might be a reusable function? seems not
            with affected_source_dimensions_and_existing_nodes as (
                    select
                        adim.sourceorigindimensionhash,
                        originnode.*,
                        copycheck.is_copy_necessary
                    from jsonb_array_elements_text(:affectedsourcedimensionspacepoints) adim(sourceorigindimensionhash)
                        -- get source node
                        left join {$this->tableNames->functionFindNodeByOrigin()}(
                                :nodeaggregateid,
                                :contentstreamid,
                                adim.sourceorigindimensionhash
                            ) originnode on true
                        -- check, if we need to copy or just update
                        left join lateral (
                            select count(distinct h.contentstreamid) > 1 as is_copy_necessary
                            from {$this->tableNames->hierarchyRelation()} h
                            where originnode.relationanchorpoint = any(h.childnodeanchors)
                        ) copycheck on true
                ),
                copy_node_on_write as (
                    insert into {$this->tableNames->node()}
                        (nodeaggregateid, origindimensionspacepoint, origindimensionspacepointhash,
                         nodetypename, classification, nodename, properties)
                    select
                        sn.nodeaggregateid,
                        sn.origindimensionspacepoint,
                        sn.origindimensionspacepointhash,
                        sn.nodetypename,
                        sn.classification,
                        sn.nodename,
                        sn.properties
                    -- all origin nodes
                    from affected_source_dimensions_and_existing_nodes sn
                    where
                      -- only, if there is more than one contentstream
                        sn.is_copy_necessary
                    returning {$this->tableNames->node()}.relationanchorpoint
                ),
                --
                -- The update case is not relevant here, since no columns are changed in the copy.
                -- A.k.a. - When references are changed, and there is only ONE content stream, we re-use the node and leave it unchanged.
                -- (that's why there is no update_single_instance CTE here as you see it in NodeModification)
                --
                -- now, reassign the hierarchy
                reassign_ingoing_hierarchy_relations as (
                    update {$this->tableNames->hierarchyRelation()}
                       set childnodeanchors = array_replace(
                              {$this->tableNames->hierarchyRelation()}.childnodeanchors,
                              asdim.relationanchorpoint,
                              cn.relationanchorpoint)
                    from copy_node_on_write cn
                        left join affected_source_dimensions_and_existing_nodes asdim
                            on asdim.relationanchorpoint = cn.relationanchorpoint
                    where asdim.relationanchorpoint = any({$this->tableNames->hierarchyRelation()}.childnodeanchors)
                      and {$this->tableNames->hierarchyRelation()}.contentstreamid = :contentstreamid
                      -- only, if there is more than one contentstream
                      and asdim.is_copy_necessary
                ),
                reassign_outgoing_hierarchy_relations as (
                    update {$this->tableNames->hierarchyRelation()}
                       set parentnodeanchor = cn.relationanchorpoint
                    from copy_node_on_write cn
                        left join affected_source_dimensions_and_existing_nodes asdim
                            on asdim.relationanchorpoint = cn.relationanchorpoint
                    where asdim.relationanchorpoint = {$this->tableNames->hierarchyRelation()}.parentnodeanchor
                      and {$this->tableNames->hierarchyRelation()}.contentstreamid = :contentstreamid
                      -- only, if there is more than one contentstream
                      and asdim.is_copy_necessary
                ),
                all_new_references as (
                    with reference_json_objects as (
                      select
                        refs.ref ->> 'referenceName' as reference_name,
                        refs.ref -> 'references' as refs_for_property
                      from jsonb_array_elements(:referencesinput) refs(ref)
                    )
                    select
                      refs.reference_name,
                      refs_for_prop.target_nodeaggregateid,
                      refs_for_prop.ref_props
                    from reference_json_objects refs
                      left join lateral (
                        select
                          refs.ref_for_prop ->> 'target' as target_nodeaggregateid,
                          refs.ref_for_prop -> 'properties' as ref_props
                        from jsonb_array_elements(refs.refs_for_property) refs(ref_for_prop)
                      ) refs_for_prop on true
                ),
                create_new_reference_records as (
                    insert into {$this->tableNames->referenceRelation()}
                        (sourcenodeanchor, "name", position, properties, targetnodeaggregateid)
                    select
                      cn.relationanchorpoint,
                      nr.reference_name,
                      0, -- todo position
                      -- convert empty array to null (as the CR expects it that way)
                      case when nr.ref_props != '[]'::jsonb then nr.ref_props end,
                      nr.target_nodeaggregateid
                    from all_new_references nr
                        left join lateral (
                            select en.relationanchorpoint
                            from affected_source_dimensions_and_existing_nodes en
                            where not en.is_copy_necessary
                            union
                            select c.relationanchorpoint
                            from copy_node_on_write c
                        ) cn on true
                )
            select 1
        SQL;

        $this->getDatabaseConnection()->executeQuery($query, $parameters);
    }

    abstract protected function getDatabaseConnection(): Connection;

}
