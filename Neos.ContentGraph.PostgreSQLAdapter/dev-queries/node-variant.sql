-- complete debugged version see: NodeVariation
with source_node as (select *
                     from neoscr_default_find_node_by_origin(
                       :nodeaggregateid,
                       :contentstreamid,
                       :origindimensionspacepointhash
                          )),
     specialized_node_copy as (
       insert into cr_default_p_graph_node
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
         returning *),
     old_covering_node as (select * -- TODO double-check if we really need * or just the ID
                           from neoscr_default_find_node_by_coverage(
                             :nodeaggregateid,
                             :contentstreamid,
                             :specializationoriginhash
                                )),
     -- ### CASE 1 - an old covering node exists - replace hierarchy
     -- Replace the old covering node with the specialized variant in
     -- all hierarchy records (child and parent references).
     update_ingoing_hierarchy as (
       update cr_default_p_graph_hierarchyrelation
         set childnodeanchors = array_replace(
           cr_default_p_graph_hierarchyrelation.childnodeanchors,
           o.relationanchorpoint,
           s.relationanchorpoint
                                )
         from old_covering_node o, specialized_node_copy s
         where o.relationanchorpoint = any (childnodeanchors)
           -- only if there is an old covering node
           and exists(select 1 from old_covering_node)),
     update_outgoing_hierarchy as (
       update cr_default_p_graph_hierarchyrelation
         set parentnodeanchor = s.relationanchorpoint
         from old_covering_node o, specialized_node_copy s
         where parentnodeanchor = o.relationanchorpoint
           -- only if there is an old covering node
           and exists(select 1 from old_covering_node))
-- ### CASE 2 - an old covering node does not exist - create hierarchy
-- Add the specialized node as child to each relation entry of all dimensions
-- of the parent node aggregate.
update cr_default_p_graph_hierarchyrelation
set childnodeanchors = childnodeanchors || (select s.relationanchorpoint from specialized_node_copy s)
from (with specialized_dimensions as (select *
                                      from jsonb_array_elements_text(:specializeddimensions) sdim(specializeddimensionhash))
      select sd.specializeddimensionhash,
             parent_hierarchy.parenthierarchyrelationanchor
      from specialized_dimensions sd
             -- source parent node aggregate ID
             left join lateral (
        select neoscr_default_get_parent_relationanchorpoint(
                 :nodeaggregateid,
                 :contentstreamid,
                 sd.specializeddimensionhash
               ) as parenthierarchyrelationanchor
        ) parent_hierarchy on true) parent_hierarchy_records
-- only if there is NO old covering node
where contentstreamid = :contentstreamid
  and dimensionspacepointhash = parent_hierarchy_records.specializeddimensionhash
  and parentnodeanchor = parent_hierarchy_records.parenthierarchyrelationanchor
  and not exists(select 1 from old_covering_node);

select
  *
from neoscr_default_find_node_by_coverage(
     'nodimer-tetherton',
     'cs-identifier',
     '78cc3f0b90b35904da870c89b97db1b0'
     );


SELECT n.origindimensionspacepoint, n.nodeaggregateid,
       n.nodetypename, n.classification, n.properties, n.nodename,
       h.contentstreamid, h.dimensionspacepoint
FROM cr_default_p_graph_hierarchyrelation h
       JOIN cr_default_p_graph_node n ON n.relationanchorpoint = ANY(h.childnodeanchors)
WHERE h.contentstreamid = :contentStreamId
  AND n.nodeaggregateid = :nodeAggregateId;

select
  -- n.*,
  h.contentstreamid,
  n.relationanchorpoint
from cr_default_p_graph_node n
  left join cr_default_p_graph_hierarchyrelation h
    on n.relationanchorpoint = any(h.childnodeanchors)
where n.nodeaggregateid = :nodeAggregateId;
  --and h.contentstreamid = :contentStreamId;
