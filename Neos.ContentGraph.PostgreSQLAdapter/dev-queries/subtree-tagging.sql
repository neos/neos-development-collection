with all_subtree_nodes as (with recursive descendant_nodes as (
  -- --------------------------------
  -- INITIAL query: select the root nodes
  -- --------------------------------
  select n.nodeaggregateid,
         n.relationanchorpoint,
         h.dimensionspacepointhash
  from cr_default_p_graph_node n
         inner join cr_default_p_graph_hierarchyrelation h
                    on n.relationanchorpoint = any (h.childnodeanchors)
  where n.nodeaggregateid = :nodeaggregateid
    and h.contentstreamid = :contentstreamid
    and h.dimensionspacepointhash in (:affecteddimensionspacepointhashes)
  union all
  -- --------------------------------
  -- RECURSIVE query: do one "child" query step
  -- --------------------------------
  select c.nodeaggregateid,
         c.relationanchorpoint,
         h.dimensionspacepointhash
  from descendant_nodes p
         inner join cr_default_p_graph_hierarchyrelation h
                    on h.parentnodeanchor = p.relationanchorpoint
         inner join cr_default_p_graph_node c
                    on c.relationanchorpoint = any (h.childnodeanchors)
  where h.contentstreamid = :contentstreamid
    and h.dimensionspacepointhash in (:affecteddimensionspacepointhashes))
                           select dn.nodeaggregateid,
                                  dn.dimensionspacepointhash
                           from descendant_nodes dn)
select subt.dimensionspacepointhash,
       array_agg(subt.nodeaggregateid) as affected_nodeaggregateids
from all_subtree_nodes subt
group by subt.dimensionspacepointhash
;


select '{"existing": true}'::jsonb || null;
select '{"existing": true}'::jsonb || '{"new": true}'::jsonb;
