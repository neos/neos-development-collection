BEGIN;

INSERT INTO "namespaces" ("prefix", "uri") VALUES ('jcr', 'http://www.jcp.org/jcr/1.0');
INSERT INTO "namespaces" ("prefix", "uri") VALUES ('nt', 'http://www.jcp.org/jcr/nt/1.0');
INSERT INTO "namespaces" ("prefix", "uri") VALUES ('mix', 'http://www.jcp.org/jcr/mix/1.0');
INSERT INTO "namespaces" ("prefix", "uri") VALUES ('xml', 'http://www.w3.org/XML/1998/namespace');
INSERT INTO "namespaces" ("prefix", "uri") VALUES ('flow3', 'http://forge.typo3.org/namespaces/flow3');

INSERT INTO "nodetypes" ("name","namespace") VALUES ('base','http://www.jcp.org/jcr/nt/1.0');
INSERT INTO "nodetypes" ("name","namespace") VALUES ('unstructured','http://www.jcp.org/jcr/nt/1.0');

-- INSERT INTO "nodes" ("identifier", "name", "namespace", "parent", "nodetype", "nodetypenamespace") VALUES ('a-fresh-uuid-here', '', '', '', 'unstructured','http://www.jcp.org/jcr/nt/1.0');

COMMIT;
