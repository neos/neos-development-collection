-- #### create root node


-- upsert
-- -> save object

-- select *
-- if (exists) -> UPDATE
-- else -> INSERT


-- insert
-- select -> ID
-- insert -> FK

-- #### create regular node
with created_node as (
  -- first, we create the node record
  insert into cr_default_p_graph_node
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
into cr_default_p_graph_hierarchyrelation
(contentstreamid, parentnodeanchor, dimensionspacepointhash, dimensionspacepoint, childnodeanchors)
-- contentstream and parent ID is passed via parameter
select :contentstreamid        as contentstreamid,
       pn.relationanchorpoint  as parentnodeanchor,
       sibl.dimensionhash      as dimensionspacepointhash,
       dsp.dimensionspacepoint as dimensionspacepoint,
       array [cn.relationanchorpoint]
-- here we access the created node ID
from created_node cn
       -- we pass in the target dimensions and successors via JSON object parameter
       -- jsonb_each_text transforms the JSON object key-values to rows
       left join jsonb_each_text(:interdimensional_siblings) sibl(dimensionhash, successor)
                 on true
  -- here, we access the dimension values to copy them on the hierarchy record
       left join cr_default_p_graph_dimensionspacepoints dsp
                 on dsp.hash = sibl.dimensionhash
  -- The parent relation input is a **Node Aggregate ID**, but we need the anchorpoint
       left join lateral (
  select pn.relationanchorpoint
  from cr_default_p_graph_node pn
         left join cr_default_p_graph_hierarchyrelation ph
                   on ph.childnodeanchors = any (pn.relationanchorpoint)
  where ph.contentstreamid = :contentstreamid
    and ph.dimensionspacepoint = sibl.dimensionhash
    and pn.nodeaggregateid = :parentnodeaggregateid
  ) pn on true
-- ### parent hierarchy entry already exists (UPDATE) - there are siblings for the new node
-- the primary key is multi-column, so we check for the named constraint
on conflict on constraint cr_default_p_graph_hierarchyrelation_pkey
  do update
  -- sort in the node in the child-node array
  set childnodeanchors = insert_into_array_before_successor(
    childnodeanchors,
    cn.relationanchorpoint,
    -- the successor is optional, if none is given, it is appended at the end of the array
    sibl.successor);





--- testing stuff

select *
from jsonb_each_text('{
  "foo": "123",
  "bar": "nice"
}');


select *
from jsonb_array_elements_text('["a", "b"]');



-- first, we create the node record
insert into cr_default_p_graph_node
    (nodeaggregateid, origindimensionspacepoint, origindimensionspacepointhash, nodetypename,
     properties, classification, nodename)
-- all values are passed via parameter
values (:nodeaggregateid, :origindimensionspacepoint, :origindimensionspacepointhash, :nodetypename, :properties,
        :classification, :nodename)
on conflict on constraint idx_id do update set properties = '{}';
