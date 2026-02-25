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

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Neos\ContentGraph\PostgreSQLAdapter\ContentGraphTableNames;
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
    abstract protected function getTableNames(): ContentGraphTableNames;

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
              insert into {$this->getTableNames()->dimensionSpacePoints()}
                (hash, dimensionspacepoint)
              select
                dim.dimensionhash,
                dim.dimensionvalues
              from jsonb_each(:dimensions) dim(dimensionhash, dimensionvalues) -- they are a query parameter JSON object
              on conflict do nothing -- TODO validate, if this is correct behavior
            ),
            created_node as (
              -- then, we create the node record
              insert into {$this->getTableNames()->node()}
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
            into {$this->getTableNames()->hierarchyRelation()}
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
                {$this->getTableNames()->hierarchyRelation()}.childnodeanchors,
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
        //  3. Inherit parent subtree tags to the newly created child node (via separate UPDATE)

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
            // empty string means "unnamed"
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
                        select {$this->getTableNames()->functionGetRelationAnchorPoint()}(
                                sibl.successor,
                                :contentstreamid,
                                sibl.dimensionhash
                             ) as relationanchorpoint
                    ) r on true
            ),
            created_node as (
              -- first, we create the node record
              insert into {$this->getTableNames()->node()}
                (nodeaggregateid, origindimensionspacepoint, origindimensionspacepointhash, nodetypename,
                 properties, classification, nodename)
                -- all values are passed via parameter
                values (:nodeaggregateid, :origindimensionspacepoint, :origindimensionspacepointhash, :nodetypename, :properties,
                        :classification, :nodename)
                -- we want to keep track of the created ID (it is auto-increment)
                returning relationanchorpoint)
            -- now we connect the hierarchy for each content dimension (this node needs to be placed below its parent)
            -- ### initial case (INSERT) - this node is the first child node of its parent
            insert
            into {$this->getTableNames()->hierarchyRelation()}
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
                   left join {$this->getTableNames()->dimensionSpacePoints()} dsp
                             on dsp.hash = sibl.dimensionhash
                -- The parent relation input is a **Node Aggregate ID**, but we need the anchorpoint
                   left join lateral (
                       select {$this->getTableNames()->functionGetRelationAnchorPoint()}(
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
                {$this->getTableNames()->hierarchyRelation()}.childnodeanchors,
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
        SQL;
        $this->getDatabaseConnection()->executeQuery($query, $parameters);

        // ##### second query: inherit parent's subtree tags to the newly created child node.
        // The parent's tags come from its incoming hierarchy relation.
        // All parent tags become inherited (null value) for the child.
        $inheritTagsQuery = <<<SQL
            UPDATE {$this->getTableNames()->hierarchyRelation()} child_h
            SET subtreetags = jsonb_set(
                COALESCE(child_h.subtreetags, '{}'),
                ARRAY[cn.relationanchorpoint::text],
                (
                    -- Get parent's tags and convert all values to null (inherited)
                    SELECT COALESCE(
                        (SELECT jsonb_object_agg(key, null)
                         FROM jsonb_each(
                             COALESCE(parent_h.subtreetags->(pn.relationanchorpoint::text), '{}')
                         )),
                        '{}'
                    )
                    FROM {$this->getTableNames()->hierarchyRelation()} parent_h
                    JOIN {$this->getTableNames()->node()} pn
                        ON pn.relationanchorpoint = ANY(parent_h.childnodeanchors)
                    WHERE pn.nodeaggregateid = :parentnodeaggregateid
                      AND parent_h.contentstreamid = :contentstreamid
                      AND parent_h.dimensionspacepointhash = child_h.dimensionspacepointhash
                    LIMIT 1
                )
            )
            FROM {$this->getTableNames()->node()} cn
            WHERE cn.nodeaggregateid = :nodeaggregateid
              AND cn.relationanchorpoint = ANY(child_h.childnodeanchors)
              AND child_h.contentstreamid = :contentstreamid
              AND child_h.dimensionspacepointhash IN (:dimensionspacepointhashes)
              -- Only update if the parent actually has tags
              AND EXISTS (
                  SELECT 1
                  FROM {$this->getTableNames()->hierarchyRelation()} ph
                  JOIN {$this->getTableNames()->node()} ppn
                      ON ppn.relationanchorpoint = ANY(ph.childnodeanchors)
                  WHERE ppn.nodeaggregateid = :parentnodeaggregateid
                    AND ph.contentstreamid = :contentstreamid
                    AND ph.dimensionspacepointhash = child_h.dimensionspacepointhash
                    AND COALESCE(ph.subtreetags->(ppn.relationanchorpoint::text), '{}') != '{}'::jsonb
              )
        SQL;

        $this->getDatabaseConnection()->executeStatement($inheritTagsQuery, [
            'contentstreamid' => $event->contentStreamId->value,
            'nodeaggregateid' => $event->nodeAggregateId->value,
            'parentnodeaggregateid' => $event->parentNodeAggregateId->value,
            'dimensionspacepointhashes' => $event->succeedingSiblingsForCoverage->toDimensionSpacePointSet()->getPointHashes(),
        ], [
            'dimensionspacepointhashes' => ArrayParameterType::STRING,
        ]);
    }

}
