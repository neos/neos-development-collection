select
  array[
  ];

-- TODO maybe add a type - for now, I use JSON
-- create type node_subtree_position AS (
--                          r       double precision,
--                          i       double precision
--                        );

-- NOTE: it is crucially important that *inside* a single level, we
-- additionally order by ordinality (i.e. sort order of the childnodeanchor list)
-- to preserve node ordering when fetching subtrees.
-- ORDER BY level DESC, ordinality;

create materialized view neos_subtree as (

);



with recursive subtree as (
  select
    n.*,
    h.contentstreamid,
    h.dimensionspacepoint,
    null::varchar AS parent_node,
    0 as level,
    h.ordinality
  from cr_default_p_graph_node n
         inner join (
    select *
    from cr_default_p_graph_hierarchyrelation h,
         -- this creates a new generated column "ordinality" which contains the sorting
         -- order of the childnodeanchor entries. We use this on the top level query to
         -- ensure that we preserve sorting of child nodes.
         unnest(h.childnodeanchors) with ordinality childnodeanchor
  ) h on n.relationanchorpoint = h.childnodeanchor
  where n.nodeaggregateid = :nodeaggregateid
    and h.contentstreamid = :contentstreamid
    and h.dimensionspacepointhash = :dimensionspacepointhash
  union all
  -- --------------------------------
  -- RECURSIVE query: do one "child" query step, taking into account the depth and node type constraints
  -- --------------------------------
  select
    cn.*,
    ch.contentstreamid,
    ch.dimensionspacepoint,
    p.nodeaggregateid as parent_node,
    p.level + 1 as level,
    ch.ordinality
  from subtree p
         inner join (
    select *
    from cr_default_p_graph_hierarchyrelation h,
         -- this creates a new generated column "ordinality" which contains the sorting
         -- order of the childnodeanchor entries. We use this on the top level query to
         -- ensure that we preserve sorting of child nodes.
         unnest(h.childnodeanchors) with ordinality childnodeanchor
  ) ch on ch.parentnodeanchor = p.relationanchorpoint
         inner join cr_default_p_graph_node cn on cn.relationanchorpoint = any(ch.childnodeanchors)
  where ch.contentstreamid = :contentstreamid
    and ch.dimensionspacepointhash = :dimensionspacepointhash
)
select * from subtree
;


select
  cn.*,
  ch.*
from (
  select *
  from cr_default_p_graph_hierarchyrelation h,
       -- this creates a new generated column "ordinality" which contains the sorting
       -- order of the childnodeanchor entries. We use this on the top level query to
       -- ensure that we preserve sorting of child nodes.
       unnest(h.childnodeanchors) with ordinality childnodeanchor
) ch
       inner join cr_default_p_graph_node cn on cn.relationanchorpoint = any(ch.childnodeanchors)
where ch.parentnodeanchor = 1
  and ch.contentstreamid = :contentstreamid
  and ch.dimensionspacepointhash = :dimensionspacepointhash;


-- #### actual view query

select
  outer_n.relationanchorpoint,
  outer_n.nodeaggregateid,
  outer_h.dimensionspacepointhash,
  outer_h.dimensionspacepoint,
  node_subtree.*
from cr_default_p_graph_node outer_n
       left join cr_default_p_graph_hierarchyrelation outer_h
                 on outer_n.relationanchorpoint = any(outer_h.childnodeanchors)
       left join lateral (
  with recursive subtree as (
    select
      n.*,
      h.contentstreamid,
      h.dimensionspacepoint,
      null::varchar AS parent_node,
      0 as level,
      h.ordinality
    from cr_default_p_graph_node n
           inner join (
      select *
      from cr_default_p_graph_hierarchyrelation h,
           -- this creates a new generated column "ordinality" which contains the sorting
           -- order of the childnodeanchor entries. We use this on the top level query to
           -- ensure that we preserve sorting of child nodes.
           unnest(h.childnodeanchors) with ordinality childnodeanchor
    ) h on n.relationanchorpoint = h.childnodeanchor
    where n.nodeaggregateid = outer_n.nodeaggregateid
      and h.contentstreamid = outer_h.contentstreamid
      and h.dimensionspacepointhash = outer_h.dimensionspacepointhash
    union all
    -- --------------------------------
    -- RECURSIVE query: do one "child" query step, taking into account the depth and node type constraints
    -- --------------------------------
    select
      cn.*,
      ch.contentstreamid,
      ch.dimensionspacepoint,
      p.nodeaggregateid as parent_node,
      p.level + 1 as level,
      ch.ordinality
    from subtree p
           inner join (
      select *
      from cr_default_p_graph_hierarchyrelation h,
           -- this creates a new generated column "ordinality" which contains the sorting
           -- order of the childnodeanchor entries. We use this on the top level query to
           -- ensure that we preserve sorting of child nodes.
           unnest(childnodeanchors) with ordinality childnodeanchor
    ) ch on ch.parentnodeanchor = p.relationanchorpoint
           inner join cr_default_p_graph_node cn on cn.relationanchorpoint = ch.childnodeanchor
    where ch.contentstreamid = outer_h.contentstreamid
      and ch.dimensionspacepointhash = outer_h.dimensionspacepointhash
  )
  select
    array_agg(st.relationanchorpoint) affected_anchors,
    array_agg(st.nodeaggregateid) affected_aggregateids,
    jsonb_agg(jsonb_build_object(
      'nodeaggregateid', st.nodeaggregateid,
      'parent', st.parent_node,
      'level', st.level,
      'ordinality', st.ordinality
              )) as subtree_structure
  from subtree st
  ) node_subtree on true;




































