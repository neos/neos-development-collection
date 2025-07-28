select '[{"referenceName":"referenceProperty","references":[{"target":"anthony-destinode","properties":[]}]}]'::jsonb;

with reference_json_objects as (
  select
    refs.ref -> 'referenceName' as reference_name,
    refs.ref -> 'references' as refs_for_property
  -- from jsonb_array_elements('[{"referenceName":"referenceProperty","references":[{"target":"anthony-destinode","properties":[]}, {"target":"anthony-destinode2","properties":[]}]}, {"referenceName":"referenceProperty2","references":[{"target":"anthony-destinode","properties":[]}]}]') refs(ref)
    from jsonb_array_elements('[{"referenceName":"referenceProperty","references":[]}]') refs(ref)
)
select
  refs.reference_name,
  refs_for_prop.target_nodeaggregateid,
  refs_for_prop.ref_props,
  row_number() over (partition by refs.reference_name) as position_offset
from reference_json_objects refs
  left join lateral (
    select
      refs.ref_for_prop -> 'target' as target_nodeaggregateid,
      refs.ref_for_prop -> 'properties' as ref_props
    from jsonb_array_elements(refs.refs_for_property) refs(ref_for_prop)
  ) refs_for_prop on true;

