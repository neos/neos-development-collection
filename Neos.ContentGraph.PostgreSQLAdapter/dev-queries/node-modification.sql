select '{
  "string" : {
    "value" : "My new string",
    "type" : "string"
  },
  "int" : {
    "value" : 8472,
    "type" : "integer"
  },
  "float" : {
    "value" : 72.84,
    "type" : "float"
  }
  }'::jsonb - (select array_agg(el.propertyname) from jsonb_array_elements_text('["string", "int"]') el(propertyname));

select '{
  "string" : {
    "value" : "My new string",
    "type" : "string"
  },
  "int" : {
    "value" : 8472,
    "type" : "integer"
  },
  "float" : {
    "value" : 72.84,
    "type" : "float"
  }
}'::jsonb - coalesce((
    select array_agg(el.propertyname)
             from jsonb_array_elements_text('[]') el(propertyname)
 ), array[]::text[]);
