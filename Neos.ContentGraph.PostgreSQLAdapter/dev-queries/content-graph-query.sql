-- ### find NodeAggregate

-- input: nodeaggregateid, contentstreamid
with aggregate_nodes as (
  -- find all node variants for this aggregate ID
  select *
  from cr_default_p_graph_node an
  where an.nodeaggregateid = :nodeaggregateid)
select an.nodetypename,
       an.nodename,
       an.classification,
       jsonb_object_agg(an.origindimensionspacepointhash, an.origindimensionspacepoint)
                                                             as occupieddimensionspacepoints,
       jsonb_object_agg(an.origindimensionspacepointhash, jsonb_build_object(
         'dimensionspacepoint', h.dimensionspacepoint,
         'origindimensionspacepoint', an.origindimensionspacepoint,
         'properties', an.properties
                                                          )) as nodes_by_occupied_dsp´,
       jsonb_object_agg(an.origindimensionspacepointhash, jsonb_build_object(
         h.dimensionspacepointhash, h.dimensionspacepoint
                                                          )) as coverage_by_occupant,
       jsonb_object_agg(h.dimensionspacepointhash, an.origindimensionspacepoint)
                                                             as occupation_by_covered,
       jsonb_object_agg(h.dimensionspacepointhash, subtree_tags.tags)
                                                             as subtreetags_by_covered
-- aggregations
from aggregate_nodes an
       -- hierarchy relation for variants
       left join cr_default_p_graph_hierarchyrelation h
                 on an.relationanchorpoint = any (h.childnodeanchors)
                   and h.contentstreamid = :contentstreamid
  -- subtree tags for each variant
  -- TODO mehr subtree logik / vererbung? let's see...
       left join lateral (
  with all_affected_subtrees as (select *
                                 from cr_default_p_graph_subtreetags st
                                 where :nodeaggregateid = any (st.affectednodeaggregateids)
                                   and st.contentstreamid = :contentstreamid
                                   and st.dimensionspacepointhash = h.dimensionspacepointhash)
  select
    -- Since there is no removal of tags down the inheritance chain,
    -- we can simply add together all parent tags without having to look at the
    -- inheritance chain order.
    jsonb_build_object(
      'with_inherited', (select jsonb_agg(t.tag)
                         from (select distinct unnest(expl_st.subtreetags)
                               from all_affected_subtrees expl_st) t(tag)),
      'only_inherited', (select jsonb_agg(t.tag)
                         from (select distinct unnest(expl_st.subtreetags)
                               from all_affected_subtrees expl_st
                               -- exclude explicitly set tags
                               where expl_st.nodeaggregateid != :nodeaggregateid) t(tag))
    ) as tags
  ) subtree_tags on true
group by an.nodetypename, an.nodename, an.classification;



with fake_table(col) as (select array ['foo', 'bar']
                         union
                         select array ['bar', 'baz']
                         union
                         select array ['hallo', 'welt'])
select jsonb_agg(c)
from (select distinct unnest(col)
      from fake_table) a(c);
select jsonb_agg();
