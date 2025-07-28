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
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\EventCouldNotBeAppliedToContentGraph;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\NodeRecord;
use Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection\ProjectionReadQueries;
use Neos\ContentRepository\Core\Feature\NodeModification\Event\NodePropertiesWereSet;

/**
 * The node modification feature set for the hypergraph projector
 *
 * @internal
 */
trait NodeModification
{
    use CopyOnWrite;

    /**
     * @throws \Throwable
     */
    private function whenNodePropertiesWereSet(NodePropertiesWereSet $event): void
    {

        // copy on write
        $parameters = [
            'contentstreamid' => $event->contentStreamId->value,
            'nodeaggregateid' => $event->nodeAggregateId->value,
            'origindimensionspacepointhash' => $event->originDimensionSpacePoint->hash,
            'propertievalues' => json_encode($event->propertyValues->jsonSerialize()),
            'propertiestounset' => json_encode($event->propertiesToUnset->jsonSerialize())
        ];

        $query = <<<SQL
            with existing_node as (
                    select
                        {$this->tableNames->functionGetRelationAnchorPoint()}(
                            :nodeaggregateid,
                            :contentstreamid,
                            :origindimensionspacepointhash
                        ) as relationanchorpoint
                ),
                is_copy_necessary as (
                    select
                        (select count(distinct h.contentstreamid)
                         from {$this->tableNames->hierarchyRelation()} h, existing_node en
                         where en.relationanchorpoint = any(h.childnodeanchors))
                            > 1 as has_more_than_one_contentstream
                ),
                -- no copy required, since there are no other content streams
                update_single_instance as (
                    update {$this->tableNames->node()}
                    set
                        properties = (properties
                                          -- first, remove the properties to unset
                                          - coalesce((select array_agg(el.propertyname)
                                             from jsonb_array_elements_text(:propertiestounset) el(propertyname)), array[]::text[])
                                     )
                                          -- then, add the new properties
                                         || :propertievalues
                    from existing_node en, is_copy_necessary icn
                    where {$this->tableNames->node()}.relationanchorpoint = en.relationanchorpoint
                      -- only, if there is exactly one contentstream
                      and not icn.has_more_than_one_contentstream
                ),
                -- FIXME the whole copy on write might be a reusable function
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
                        -- copy the properties
                        (sn.properties
                              -- first, remove the properties to unset
                              - coalesce((select array_agg(el.propertyname)
                                 from jsonb_array_elements_text(:propertiestounset) el(propertyname)), array[]::text[])
                        )
                           -- then, add the new properties
                           || :propertievalues
                    -- source node
                    from {$this->tableNames->node()} sn, existing_node en, is_copy_necessary icn
                    where sn.relationanchorpoint = en.relationanchorpoint
                      -- only, if there is more than one contentstream
                      and icn.has_more_than_one_contentstream
                    returning {$this->tableNames->node()}.relationanchorpoint
                ),
                reassign_ingoing_hierarchy_relations as (
                    update {$this->tableNames->hierarchyRelation()}
                       set childnodeanchors = array_replace(
                              {$this->tableNames->hierarchyRelation()}.childnodeanchors,
                              en.relationanchorpoint,
                              cn.relationanchorpoint)
                    from copy_node_on_write cn, existing_node en, is_copy_necessary icn
                    where en.relationanchorpoint = any({$this->tableNames->hierarchyRelation()}.childnodeanchors)
                      and {$this->tableNames->hierarchyRelation()}.contentstreamid = :contentstreamid
                      -- only, if there is more than one contentstream
                      and icn.has_more_than_one_contentstream
                ),
                reassign_outgoing_hierarchy_relations as (
                    update {$this->tableNames->hierarchyRelation()}
                       set parentnodeanchor = cn.relationanchorpoint
                    from copy_node_on_write cn, existing_node en, is_copy_necessary icn
                    where en.relationanchorpoint = {$this->tableNames->hierarchyRelation()}.parentnodeanchor
                      and {$this->tableNames->hierarchyRelation()}.contentstreamid = :contentstreamid
                      -- only, if there is more than one contentstream
                      and icn.has_more_than_one_contentstream
                )
                -- TODO copy references
            select 1
        SQL;

        $this->getDatabaseConnection()->executeQuery($query, $parameters);
    }

    abstract protected function getDatabaseConnection(): Connection;
}
