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
from jsonb_array_elements_text('[
  "a",
  "b"
]');


-- ####### root node creation
do
$$
  declare
    _nodeaggregateid               text  := 'lady-eleonode-rootford';
    _origindimensionspacepoint     jsonb := '[]';
    _origindimensionspacepointhash text  := 'd751713988987e9331980363e24189ce';
    _nodetypename                  text  := 'Neos.ContentRepository:Root';
    _classification                text  := 'root';
    _contentstreamid               text  := 'cs-identifier';
    _dimensions                    jsonb := '{"d751713988987e9331980363e24189ce":[]}';
    _rootedgeanchor bigint := 0;
  begin

    with created_dsps as ( -- first, we create the dimension space point entries...
      insert into cr_default_p_graph_dimensionspacepoints
        (hash, dimensionspacepoint)
        select dim.dimensionhash,
               dim.dimensionvalues
        from jsonb_each(_dimensions) dim(dimensionhash, dimensionvalues) -- they are a query parameter JSON object
        on conflict do nothing -- TODO validate, if this is correct behavior
    ),
         created_node as (
           -- then, we create the node record
           insert into cr_default_p_graph_node
             (nodeaggregateid, origindimensionspacepoint, origindimensionspacepointhash, nodetypename,
              properties, classification, nodename)
             -- all values are passed via parameter
             values (_nodeaggregateid, _origindimensionspacepoint, _origindimensionspacepointhash, _nodetypename,
                     '{}', -- empty properties
                     _classification,
                     '' -- no node name
                    )
             -- we want to keep track of the created ID (it is auto-increment)
             returning relationanchorpoint)
    -- now we connect the hierarchy for each content dimension (this node needs to be placed below its parent)
-- ### this node is the first child node of its parent
    insert
    into cr_default_p_graph_hierarchyrelation
    (contentstreamid, parentnodeanchor, dimensionspacepointhash, dimensionspacepoint, childnodeanchors)
-- contentstream and root edge is passed via parameter
    select _contentstreamid        as contentstreamid,
           _rootedgeanchor         as parentnodeanchor,
           dim.dimensionhash       as dimensionspacepointhash,
           dim.dimensionspacepoint as dimensionspacepoint,
           array [cn.relationanchorpoint]
-- here we access the created node ID
    from created_node cn
           -- we pass in the target dimensions via JSON object parameter
           left join jsonb_each(_dimensions) dim(dimensionhash, dimensionspacepoint)
                     on true
    -- ### parent hierarchy entry already exists (UPDATE) - there are siblings for the new node
    -- the primary key is multi-column, so we check for the named constraint
    -- fixme dynamic name
    on conflict on constraint cr_default_p_graph_hierarchyrelation_pkey
      do update
-- append the node in the child-node array
      set childnodeanchors = insert_into_array_before_successor(
        cr_default_p_graph_hierarchyrelation.childnodeanchors,
        excluded.childnodeanchors[1],
        -- There is no order of the root nodes.
        -- Root nodes live in the childnodes array of a single root node edge row.
        null);

  end
$$;
