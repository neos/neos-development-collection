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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRelationAnchorPoint;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeCreation\Event\NodeAggregateWithNodeWasCreated;
use Neos\ContentRepository\Core\Feature\RootNodeCreation\Event\RootNodeAggregateWithNodeWasCreated;

/**
 * The node creation feature set for the hypergraph projector
 *
 * @internal
 */
trait NodeCreation
{

    abstract protected function getDatabaseConnection(): Connection;

    /*
 * TODO error handling
if (is_null($parentNode)) {
    throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(
        get_class($event)
    );
}
*/

    /**
     * @throws \Throwable
     */
    private function whenRootNodeAggregateWithNodeWasCreated(RootNodeAggregateWithNodeWasCreated $event): void
    {
        //  1. Create the given Dimension Space Point entries
        //  2. Create a Node entry
        //  3. Connect the Hierarchy to the root edge (add the node as child-node in each content dimension)

        $originDimensionSpacePoint = OriginDimensionSpacePoint::createWithoutDimensions();
        $parameters = [
            'nodeaggregateid' => $event->nodeAggregateId->value,
            'origindimensionspacepoint' => $originDimensionSpacePoint->toJson(),
            'origindimensionspacepointhash' => $originDimensionSpacePoint->hash,
            'nodetypename' => $event->nodeTypeName->value,
            'classification' => $event->nodeAggregateClassification->value,
            'contentstreamid' => $event->contentStreamId->value,
            // This is an JSON object where the keys are the dimension hash
            // and the values are the successors (optional, null value means -> append child to end)
            'dimensions' => json_encode($event->coveredDimensionSpacePoints->points),
            // this could be done directly in the query (value 0), but I leave it here for verbosity
            // and code usage navigation
            'rootedgeanchor' => NodeRelationAnchorPoint::forRootEdge()
        ];

        $query = <<<SQL
            with created_dsps as ( -- first, we create the dimension space point entries...
              insert into {$this->tableNames->dimensionSpacePoints()}
                (hash, dimensionspacepoint)
              select
                dim.dimensionhash,
                dim.dimensionvalues
              from jsonb_each(:dimensions) dim(dimensionhash, dimensionvalues) -- they are a query parameter JSON object
              on conflict do nothing -- TODO validate, if this is correct behavior
            ),
            created_node as (
              -- then, we create the node record
              insert into {$this->tableNames->node()}
                (nodeaggregateid, origindimensionspacepoint, origindimensionspacepointhash, nodetypename,
                 properties, classification, nodename)
                -- all values are passed via parameter
                values (:nodeaggregateid, :origindimensionspacepoint, :origindimensionspacepointhash, :nodetypename,
                        '{}', -- empty properties
                        :classification,
                        '' -- no node name
                        )
                -- we want to keep track of the created ID (it is auto-increment)
                returning relationanchorpoint)
            -- now we connect the hierarchy for each content dimension (this node needs to be placed below its parent)
            -- ### this node is the first child node of its parent
            insert
            into {$this->tableNames->hierarchyRelation()}
            (contentstreamid, parentnodeanchor, dimensionspacepointhash, dimensionspacepoint, childnodeanchors)
            -- contentstream and root edge is passed via parameter
            select :contentstreamid        as contentstreamid,
                   :rootedgeanchor         as parentnodeanchor,
                   dim.dimensionhash       as dimensionspacepointhash,
                   dim.dimensionspacepoint as dimensionspacepoint,
                   array [cn.relationanchorpoint]
            -- here we access the created node ID
            from created_node cn
                   -- we pass in the target dimensions via JSON object parameter
                   left join jsonb_each(:dimensions) dim(dimensionhash, dimensionspacepoint)
                             on true
            -- ### parent hierarchy entry already exists (UPDATE) - there are siblings for the new node
            -- the primary key is multi-column, so we check for the named constraint
            -- fixme dynamic name
            on conflict on constraint cr_default_p_graph_hierarchyrelation_pkey
              do update
              -- append the node in the child-node array
              set childnodeanchors = insert_into_array_before_successor(
                -- by aliasing with the table name, we access the original existing value
                {$this->tableNames->hierarchyRelation()}.childnodeanchors,
                -- 'exluded' alias references the insert data that was rejected by constraint
                -- we cannot access 'cn' alias here
                excluded.childnodeanchors[1],
                -- There is no order of the root nodes.
                -- Root nodes live in the childnodes array of a single root node edge row.
                null)
        SQL;

        $this->getDatabaseConnection()->executeQuery($query, $parameters);
    }

    /**
     * @param NodeAggregateWithNodeWasCreated $event
     * @throws \Throwable
     */
    public function whenNodeAggregateWithNodeWasCreated(NodeAggregateWithNodeWasCreated $event): void
    {
        // This event handler performs the following actions:
        //  1. Create a node entry
        //  2. Connect the hierarchy (add the node as child-node in each content dimension)

        // the query requires the interdimensional siblings as JSON object (key: hash, value: sibling aggregate ID)
        $siblings = [];
        foreach ($event->succeedingSiblingsForCoverage->items as $sibling) {
            $siblings[$sibling->dimensionSpacePoint->hash] = $sibling->nodeAggregateId?->value;
        }

        $parameters = [
            'nodeaggregateid' => $event->nodeAggregateId->value,
            'origindimensionspacepoint' => $event->originDimensionSpacePoint->toJson(),
            'origindimensionspacepointhash' => $event->originDimensionSpacePoint->hash,
            'nodetypename' => $event->nodeTypeName->value,
            'properties' => json_encode($event->initialPropertyValues),
            'classification' => $event->nodeAggregateClassification->value,
            // empty string means "unnamend"
            'nodename' => $event->nodeName !== null ? $event->nodeName->value : '',
            'contentstreamid' => $event->contentStreamId->value,
            'parentnodeaggregateid' => $event->parentNodeAggregateId->value,
            // This is an JSON object where the keys are the dimension hash
            // and the values are the successors (optional, null value means -> append child to end)
            'interdimensionalsiblings' => json_encode($siblings)
        ];

        $query = <<<SQL
            with dimensions_and_successor as (
                -- we pass in the target dimensions and successors via JSON object parameter
                -- jsonb_each_text transforms the JSON object key-values to rows
                select sibl.dimensionhash, r.relationanchorpoint
                from jsonb_each_text(:interdimensionalsiblings) sibl(dimensionhash, successor)
                  -- The successor input is a **Node Aggregate ID**, but we need the anchorpoint
                    left join lateral (
                        select {$this->tableNames->functionGetRelationAnchorPoint()}(
                                sibl.successor,
                                :contentstreamid,
                                sibl.dimensionhash
                             ) as relationanchorpoint
                    ) r on true
            ),
            created_node as (
              -- first, we create the node record
              insert into {$this->tableNames->node()}
                (nodeaggregateid, origindimensionspacepoint, origindimensionspacepointhash, nodetypename,
                 properties, classification, nodename)
                -- all values are passed via parameter
                values (:nodeaggregateid, :origindimensionspacepoint, :origindimensionspacepointhash, :nodetypename, :properties,
                        :classification, :nodename)
                -- we want to keep track of the created ID (it is auto-increment)
                returning relationanchorpoint),
            upserted_hierarchy as (
                -- now we connect the hierarchy for each content dimension (this node needs to be placed below its parent)
                -- ### initial case (INSERT) - this node is the first child node of its parent
                insert
                into {$this->tableNames->hierarchyRelation()}
                (contentstreamid, parentnodeanchor, dimensionspacepointhash, dimensionspacepoint, childnodeanchors)
                -- contentstream and parent ID is passed via parameter
                select :contentstreamid        as contentstreamid,
                       pn.relationanchorpoint  as parentnodeanchor,
                       sibl.dimensionhash      as dimensionspacepointhash,
                       dsp.dimensionspacepoint as dimensionspacepoint,
                       array [cn.relationanchorpoint]
                -- here we access the created node ID
                from created_node cn
                       left join dimensions_and_successor sibl
                                 on true
                  -- here, we access the dimension values to copy them on the hierarchy record
                       left join {$this->tableNames->dimensionSpacePoints()} dsp
                                 on dsp.hash = sibl.dimensionhash
                  -- The parent relation input is a **Node Aggregate ID**, but we need the anchorpoint
                       left join lateral (
                           select {$this->tableNames->functionGetRelationAnchorPoint()}(
                                    :parentnodeaggregateid,
                                    :contentstreamid,
                                    sibl.dimensionhash
                                 ) as relationanchorpoint
                       ) pn on true
                -- ### parent hierarchy entry already exists (UPDATE) - there are siblings for the new node
                -- the primary key is multi-column, so we check for the named constraint
                -- fixme dynamic name
                on conflict on constraint cr_default_p_graph_hierarchyrelation_pkey
                  do update
                  -- sort in the node in the child-node array
                  set childnodeanchors = insert_into_array_before_successor(
                    -- by aliasing with the table name, we access the original existing value
                    {$this->tableNames->hierarchyRelation()}.childnodeanchors,
                    -- 'exluded' alias references the insert data that was rejected by constraint
                    -- we cannot access 'cn' alias here
                    excluded.childnodeanchors[1],
                    -- the successor is optional, if none is given, it is appended at the end of the array
                    (
                        select s.relationanchorpoint
                        from dimensions_and_successor s
                        where s.dimensionhash = excluded.dimensionspacepointhash
                    )
                  )
                returning contentstreamid, parentnodeanchor, dimensionspacepointhash,
                      -- see https://sigpwned.com/2023/08/10/postgres-upsert-created-or-updated/
                      (xmax = 0) AS _created
            )
            -- return values for result debugging in PHP
            select
                cn.relationanchorpoint as created_node_relationanchorpoint,
                (select jsonb_agg(jsonb_build_object(
                    'contentstreamid', h.contentstreamid,
                    'parentnodeanchor', h.parentnodeanchor,
                    'dimensionspacepointhash', h.dimensionspacepointhash,
                    'created', h._created
                )) from upserted_hierarchy h) as upserted_hierarchy
            from created_node cn
        SQL;

        $result = $this->getDatabaseConnection()->executeQuery($query, $parameters);
        $row = $result->fetchAssociative();

        $createdNodeRelationAnchorPoint = $row['created_node_relationanchorpoint'];
        $upsertedHierarchyRecords = json_decode($row['upserted_hierarchy'], true);

        if (count($upsertedHierarchyRecords) === null) {
            // TODO error handling and/or logging?
        }

        // TODO sub-tree tags
        /*
         * TODO error handling
        if (is_null($parentNode)) {
            throw EventCouldNotBeAppliedToContentGraph::becauseTheTargetParentNodeIsMissing(
                get_class($event)
            );
        }
        */
    }

}
