select array [
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

create materialized view neos_subtree as
(

);



with recursive subtree as (select n.*,
                                  h.contentstreamid,
                                  h.dimensionspacepoint,
                                  null::varchar AS parent_node,
                                  0             as level,
                                  h.ordinality
                           from cr_default_p_graph_node n
                                  inner join (select *
                                              from cr_default_p_graph_hierarchyrelation h,
                                                   -- this creates a new generated column "ordinality" which contains the sorting
                                                   -- order of the childnodeanchor entries. We use this on the top level query to
                                                   -- ensure that we preserve sorting of child nodes.
                                                   unnest(h.childnodeanchors) with ordinality childnodeanchor) h
                                             on n.relationanchorpoint = h.childnodeanchor
                           where n.nodeaggregateid = :nodeaggregateid
                             and h.contentstreamid = :contentstreamid
                             and h.dimensionspacepointhash = :dimensionspacepointhash
                           union all
                           -- --------------------------------
                           -- RECURSIVE query: do one "child" query step, taking into account the depth and node type constraints
                           -- --------------------------------
                           select cn.*,
                                  ch.contentstreamid,
                                  ch.dimensionspacepoint,
                                  p.nodeaggregateid as parent_node,
                                  p.level + 1       as level,
                                  ch.ordinality
                           from subtree p
                                  inner join (select *
                                              from cr_default_p_graph_hierarchyrelation h,
                                                   -- this creates a new generated column "ordinality" which contains the sorting
                                                   -- order of the childnodeanchor entries. We use this on the top level query to
                                                   -- ensure that we preserve sorting of child nodes.
                                                   unnest(h.childnodeanchors) with ordinality childnodeanchor) ch
                                             on ch.parentnodeanchor = p.relationanchorpoint
                                  inner join cr_default_p_graph_node cn
                                             on cn.relationanchorpoint = any (ch.childnodeanchors)
                           where ch.contentstreamid = :contentstreamid
                             and ch.dimensionspacepointhash = :dimensionspacepointhash)
select *
from subtree
;


select cn.*,
       ch.*
from (select *
      from cr_default_p_graph_hierarchyrelation h,
           -- this creates a new generated column "ordinality" which contains the sorting
           -- order of the childnodeanchor entries. We use this on the top level query to
           -- ensure that we preserve sorting of child nodes.
           unnest(h.childnodeanchors) with ordinality childnodeanchor) ch
       inner join cr_default_p_graph_node cn on cn.relationanchorpoint = any (ch.childnodeanchors)
where ch.parentnodeanchor = 1
  and ch.contentstreamid = :contentstreamid
  and ch.dimensionspacepointhash = :dimensionspacepointhash;


-- #### actual view query

select outer_n.relationanchorpoint,
       outer_n.nodeaggregateid,
       outer_h.dimensionspacepointhash,
       outer_h.dimensionspacepoint,
       node_subtree.*
from cr_default_p_graph_node outer_n
       left join cr_default_p_graph_hierarchyrelation outer_h
                 on outer_n.relationanchorpoint = any (outer_h.childnodeanchors)
       left join lateral (
  with recursive subtree as (select n.*,
                                    h.contentstreamid,
                                    h.dimensionspacepoint,
                                    null::varchar AS parent_node,
                                    0             as level,
                                    h.ordinality
                             from cr_default_p_graph_node n
                                    inner join (select *
                                                from cr_default_p_graph_hierarchyrelation h,
                                                     -- this creates a new generated column "ordinality" which contains the sorting
                                                     -- order of the childnodeanchor entries. We use this on the top level query to
                                                     -- ensure that we preserve sorting of child nodes.
                                                     unnest(h.childnodeanchors) with ordinality childnodeanchor) h
                                               on n.relationanchorpoint = h.childnodeanchor
                             where n.nodeaggregateid = outer_n.nodeaggregateid
                               and h.contentstreamid = outer_h.contentstreamid
                               and h.dimensionspacepointhash = outer_h.dimensionspacepointhash
                             union all
                             -- --------------------------------
                             -- RECURSIVE query: do one "child" query step, taking into account the depth and node type constraints
                             -- --------------------------------
                             select cn.*,
                                    ch.contentstreamid,
                                    ch.dimensionspacepoint,
                                    p.nodeaggregateid as parent_node,
                                    p.level + 1       as level,
                                    ch.ordinality
                             from subtree p
                                    inner join (select *
                                                from cr_default_p_graph_hierarchyrelation h,
                                                     -- this creates a new generated column "ordinality" which contains the sorting
                                                     -- order of the childnodeanchor entries. We use this on the top level query to
                                                     -- ensure that we preserve sorting of child nodes.
                                                     unnest(childnodeanchors) with ordinality childnodeanchor) ch
                                               on ch.parentnodeanchor = p.relationanchorpoint
                                    inner join cr_default_p_graph_node cn on cn.relationanchorpoint = ch.childnodeanchor
                             where ch.contentstreamid = outer_h.contentstreamid
                               and ch.dimensionspacepointhash = outer_h.dimensionspacepointhash)
  select array_agg(st.relationanchorpoint) affected_anchors,
         array_agg(st.nodeaggregateid)     affected_aggregateids,
         jsonb_agg(jsonb_build_object(
           'nodeaggregateid', st.nodeaggregateid,
           'parent', st.parent_node,
           'level', st.level,
           'ordinality', st.ordinality
                   )) as                   subtree_structure
  from subtree st
  ) node_subtree on true;


-- find node by id

select n.origindimensionspacepoint,
       n.classification,
       n.nodetypename,
       n.properties,
       n.nodename,
       subtree_tags.tags
from cr_default_p_graph_node n
       left join cr_default_p_graph_hierarchyrelation h
                 on n.relationanchorpoint = any (h.childnodeanchors)
                   and h.contentstreamid = :contentstreamid
                   and h.dimensionspacepointhash = :dimensionspacepointhash
       left join lateral (
  with all_affected_subtrees as (select *
                                 from cr_default_p_graph_subtree st
                                 where n.nodeaggregateid = any (st.affected_nodeaggregateids)
                                   and st.contentstreamid = :contentstreamid
                                   and st.dimensionspacepointhash = :dimensionspacepointhash)
  select
    -- Since there is no removal of tags down the inheritance chain,
    -- we can simply add together all parent tags without having to look at the
    -- inheritance chain order.
    jsonb_build_object(
      'explicit_tags', (select jsonb_agg(t.tag)
                        from (select distinct unnest(expl_st.subtreetags)
                              from all_affected_subtrees expl_st
                              -- include only explicitly set tags
                              where expl_st.nodeaggregateid = n.nodeaggregateid) t(tag)),
      'only_inherited', (select jsonb_agg(t.tag)
                         from (select distinct unnest(expl_st.subtreetags)
                               from all_affected_subtrees expl_st
                               -- exclude explicitly set tags
                               where expl_st.nodeaggregateid != n.nodeaggregateid) t(tag))
    ) as tags
  ) subtree_tags on true
where n.nodeaggregateid = :nodeaggregateid
-- subtree tag filter
  and (
-- deactivate filter when no values are set
  not :subtreetag_filter_active
    or
  not exists(select 1
             from cr_default_p_graph_subtree st
             where :nodeaggregateid = any (st.affected_nodeaggregateids)
               and st.dimensionspacepointhash = :dimensionspacepointhash
               and st.contentstreamid = :contentstreamid
               and st.subtreetags && array [:excluded_subtreetags]::varchar(36)[])
  );


-- get absolute path

select ph.parent_nodepath_absolute || case when ph.parentnodeanchor != 0 then '/' else '' end || :nodename
from cr_default_p_graph_hierarchyrelation ph
       left join cr_default_p_graph_node pn
                 on pn.relationanchorpoint = any (ph.childnodeanchors)
where ph.contentstreamid = :contentstreamid
  and ph.dimensionspacepointhash = :dimensionspacepointhash
  and pn.nodeaggregateid = :parentnodeaggregateid;



-- ### debugging get by path

-- starting_point_anchor
select neoscr_default_get_relationanchorpoint(
         :starting_nodeaggregateid,
         :contentstreamid,
         :dimensionspacepointhash
       );

-- absolute parent path

select ph.parent_nodepath_absolute as absolute_path
from (select :start_anchor) as spa(relationanchorpoint)
       left join cr_default_p_graph_hierarchyrelation ph
                 on ph.parentnodeanchor = spa.relationanchorpoint -- FIXME here is the error
where ph.contentstreamid = :contentstreamid
  and ph.dimensionspacepointhash = :dimensionspacepointhash;

-- fixed variant
select
  -- no slash for root, otherwise messes up the concatenation later
  case when ph.parentnodeanchor = 0 then '' else ph.parent_nodepath_absolute end as absolute_path
from (select :start_anchor) as spa(relationanchorpoint)
       left join cr_default_p_graph_hierarchyrelation ph
                 on spa.relationanchorpoint = any (ph.childnodeanchors)
where ph.contentstreamid = :contentstreamid
  and ph.dimensionspacepointhash = :dimensionspacepointhash;

select n.nodeaggregateid,
       n.origindimensionspacepoint,
       n.classification,
       n.nodetypename,
       n.properties,
       n.nodename
from (select :absolute_parent_path) parent(apath),
     cr_default_p_graph_hierarchyrelation h
       left join cr_default_p_graph_node n
                 on n.relationanchorpoint = h.parentnodeanchor
where h.parent_nodepath_absolute = parent.apath || '/' || :relative_path
  and h.contentstreamid = :contentstreamid
  and h.dimensionspacepointhash = :dimensionspacepointhash;

select *
from cr_default_p_graph_hierarchyrelation h
where h.parent_nodepath_absolute = :relative_path;

-- wrong approach above... let's see

with starting_path
       as (select case when ph.parentnodeanchor = 0 then '' else ph.parent_nodepath_absolute end as absolute_path
           from cr_default_p_graph_node pn
                  left join cr_default_p_graph_hierarchyrelation ph
                            on pn.relationanchorpoint = any (ph.childnodeanchors)
           where ph.contentstreamid = :contentstreamid
             and ph.dimensionspacepointhash = :dimensionspacepointhash
             and pn.nodeaggregateid = :starting_nodeaggregateid)
select *
from starting_path;

select n.nodeaggregateid,
       n.origindimensionspacepoint,
       n.classification,
       n.nodetypename,
       n.properties,
       n.nodename
from cr_default_p_graph_hierarchyrelation h
       left join cr_default_p_graph_node n
                 on n.relationanchorpoint = h.parentnodeanchor
where h.parent_nodepath_absolute = :starting_path || :relative_parent_path
  and n.nodename = :last_path_segment
  and h.contentstreamid = :contentstreamid
  and h.dimensionspacepointhash = :dimensionspacepointhash
-- subtree tag filter
  and (
-- deactivate filter when no values are set
  not :subtreetag_filter_active
    or
  not exists(select 1
             from cr_default_p_graph_subtree st
    where n.nodeaggregateid = any (st.affected_nodeaggregateids)
      and st.dimensionspacepointhash = :dimensionspacepointhash
      and st.contentstreamid = :contentstreamid
      and st.subtreetags && array [:excluded_subtreetags]::varchar(36)[])
  );

-- lady-eleonode-rootford => ''
-- preceding-nodenborough => '/preceding-document'
-- sir-david-nodenborough => '/preceding-document'
-- succeeding-nodenborough => '/preceding-document'
-- nody-mc-nodeface => '/preceding-document/child-document'


select n.nodeaggregateid,
       n.origindimensionspacepoint,
       n.classification,
       n.nodetypename,
       n.properties,
       n.nodename,
       subtree_tags.tags
from absolute_parent_path parent_path,
     cr_default_p_graph_hierarchyrelation h
       left join cr_default_p_graph_node n
                 on n.relationanchorpoint = h.parentnodeanchor
       left join lateral (
       with all_affected_subtrees as (select *
                                      from {$this->tableNames->subTreeRelation()} st
                                      where n.nodeaggregateid = any (st.affected_nodeaggregateids)
                                        and st.contentstreamid = :contentstreamid
                                        and st.dimensionspacepointhash = :dimensionspacepointhash)
       select
         -- Since there is no removal of tags down the inheritance chain,
         -- we can simply add together all parent tags without having to look at the
         -- inheritance chain order.
         jsonb_build_object(
           'explicit_tags', (select jsonb_agg(t.tag)
                             from (select distinct unnest(expl_st.subtreetags)
                                   from all_affected_subtrees expl_st
                                   -- include only explicitly set tags
                                   where expl_st.nodeaggregateid = n.nodeaggregateid) t(tag)),
           'only_inherited', (select jsonb_agg(t.tag)
                              from (select distinct unnest(expl_st.subtreetags)
                                    from all_affected_subtrees expl_st
                                    -- exclude explicitly set tags
                                    where expl_st.nodeaggregateid != n.nodeaggregateid) t(tag))
         ) as tags
       ) subtree_tags on true
where h.parent_nodepath_absolute = parent_path.absolute_path || '/' || :relative_parent_path
  and n.nodename = :last_path_segment
  and h.contentstreamid = :contentstreamid
  and h.dimensionspacepointhash = :dimensionspacepointhash
-- subtree tag filter
  and (
-- deactivate filter when no values are set
  not :subtreetag_filter_active
    or
  not exists(select 1
             from {$this->tableNames->subTreeRelation()} st
    where n.nodeaggregateid = any (st.affected_nodeaggregateids)
      and st.dimensionspacepointhash = :dimensionspacepointhash
      and st.contentstreamid = :contentstreamid
      and st.subtreetags && array [:excluded_subtreetags]::varchar(36)[])
  )











