BEGIN;

DELETE FROM "namespaces";
DELETE FROM "nodetypes";
DELETE FROM "nodes";
DELETE FROM "properties";
DELETE FROM "stringproperties";
DELETE FROM "binaryproperties";
DELETE FROM "longproperties";
DELETE FROM "doubleproperties";
DELETE FROM "decimalproperties";
DELETE FROM "dateproperties";
DELETE FROM "booleanproperties";
DELETE FROM "nameproperties";
DELETE FROM "pathproperties";
DELETE FROM "referenceproperties";
DELETE FROM "weakreferenceproperties";
DELETE FROM "uriproperties";

INSERT INTO "namespaces" ("prefix", "uri") VALUES ('jcr', 'http://www.jcp.org/jcr/1.0');
INSERT INTO "namespaces" ("prefix", "uri") VALUES ('nt', 'http://www.jcp.org/jcr/nt/1.0');
INSERT INTO "namespaces" ("prefix", "uri") VALUES ('mix', 'http://www.jcp.org/jcr/mix/1.0');
INSERT INTO "namespaces" ("prefix", "uri") VALUES ('xml', 'http://www.w3.org/XML/1998/namespace');
INSERT INTO "namespaces" ("prefix", "uri") VALUES ('flow3', 'http://forge.typo3.org/namespaces/flow3');

INSERT INTO "nodetypes" ("name","namespace") VALUES ('base','http://www.jcp.org/jcr/nt/1.0');
INSERT INTO "nodetypes" ("name","namespace") VALUES ('unstructured','http://www.jcp.org/jcr/nt/1.0');

INSERT INTO "nodes" ("identifier", "name", "namespace", "parent", "nodetype", "nodetypenamespace") VALUES ('96b4a35d-1ef5-4a47-8b3c-0d6d69507e01', '', '', '', 'unstructured','http://www.jcp.org/jcr/nt/1.0');

COMMIT;
