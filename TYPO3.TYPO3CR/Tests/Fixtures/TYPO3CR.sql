BEGIN;

CREATE TABLE "namespaces" (
	"prefix" VARCHAR(255) PRIMARY KEY NOT NULL,
	"uri" TEXT NOT NULL,
	UNIQUE ("uri")
);
INSERT INTO "namespaces" ("prefix", "uri") VALUES ('jcr', 'http://www.jcp.org/jcr/1.0');
INSERT INTO "namespaces" ("prefix", "uri") VALUES ('nt', 'http://www.jcp.org/jcr/nt/1.0');
INSERT INTO "namespaces" ("prefix", "uri") VALUES ('mix', 'http://www.jcp.org/jcr/mix/1.0');
INSERT INTO "namespaces" ("prefix", "uri") VALUES ('xml', 'http://www.w3.org/XML/1998/namespace');
INSERT INTO "namespaces" ("prefix", "uri") VALUES ('flow3', 'http://forge.typo3.org/namespaces/flow3');

CREATE TABLE "nodetypes" (
	"name" VARCHAR(255) NOT NULL,
	"namespace" VARCHAR(255) NOT NULL DEFAULT '',
	PRIMARY KEY ("name","namespace")
);
INSERT INTO "nodetypes" ("name","namespace") VALUES ('base','http://www.jcp.org/jcr/nt/1.0');
INSERT INTO "nodetypes" ("name","namespace") VALUES ('unstructured','http://www.jcp.org/jcr/nt/1.0');

CREATE TABLE "nodes" (
  "identifier" VARCHAR(36) PRIMARY KEY NOT NULL,
  "name" VARCHAR(255) NOT NULL,
  "namespace" VARCHAR(255) NOT NULL DEFAULT '',
  "parent" VARCHAR(36) NOT NULL,
  "nodetype" VARCHAR(255),
  "nodetypenamespace" VARCHAR(255) NOT NULL DEFAULT ''
);
INSERT INTO "nodes" ("identifier", "name", "namespace", "parent", "nodetype", "nodetypenamespace") VALUES ('96b4a35d-1ef5-4a47-8b3c-0d6d69507e01', '', '', '', 'unstructured','http://www.jcp.org/jcr/nt/1.0');

CREATE TABLE "properties" (
  "parent" VARCHAR(36) NOT NULL,
  "name" VARCHAR(255) NOT NULL,
  "namespace" VARCHAR(255) NOT NULL DEFAULT '',
  "multivalue" BOOLEAN NOT NULL DEFAULT '0',
  "type" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("parent", "name", "namespace")
);
CREATE TABLE "stringproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "value" TEXT NOT NULL, PRIMARY KEY ("parent","name","namespace"));
CREATE TABLE "binaryproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "value" TEXT NOT NULL, PRIMARY KEY ("parent","name","namespace"));
CREATE TABLE "longproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "value" INTEGER NOT NULL, PRIMARY KEY ("parent","name","namespace"));
CREATE TABLE "doubleproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "value" FLOAT NOT NULL, PRIMARY KEY ("parent","name","namespace"));
CREATE TABLE "decimalproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "value" DECIMAL NOT NULL, PRIMARY KEY ("parent","name","namespace"));
CREATE TABLE "dateproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "value" TIMESTAMP NOT NULL, PRIMARY KEY ("parent","name","namespace"));
CREATE TABLE "booleanproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "value" BOOLEAN NOT NULL, PRIMARY KEY ("parent","name","namespace"));
CREATE TABLE "nameproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "value" VARCHAR(255) NOT NULL, "valuenamespace" VARCHAR(255) NOT NULL DEFAULT '', PRIMARY KEY ("parent","name","namespace"));
CREATE TABLE "pathproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '',"level" INTEGER NOT NULL, "value" TEXT NOT NULL, "valuenamespace" VARCHAR(255) NOT NULL DEFAULT '', PRIMARY KEY ("parent","name","namespace","level"));
CREATE TABLE "referenceproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "value" VARCHAR(36) NOT NULL, PRIMARY KEY ("parent","name","namespace"));
CREATE TABLE "weakreferenceproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "value" VARCHAR(36) NOT NULL, PRIMARY KEY ("parent","name","namespace"));
CREATE TABLE "uriproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "value" TEXT NOT NULL, PRIMARY KEY ("parent","name","namespace"));
CREATE TABLE "stringmultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "value" TEXT NOT NULL, PRIMARY KEY ("parent", "name", "namespace", "index"));
CREATE TABLE "binarymultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "value" TEXT NOT NULL, PRIMARY KEY ("parent", "name", "namespace", "index"));
CREATE TABLE "longmultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "value" INTEGER NOT NULL, PRIMARY KEY ("parent", "name", "namespace", "index"));
CREATE TABLE "doublemultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "value" FLOAT NOT NULL, PRIMARY KEY ("parent", "name", "namespace", "index"));
CREATE TABLE "decimalmultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "value" DECIMAL NOT NULL, PRIMARY KEY ("parent", "name", "namespace", "index"));
CREATE TABLE "datemultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "value" TIMESTAMP NOT NULL, PRIMARY KEY ("parent", "name", "namespace", "index"));
CREATE TABLE "booleanmultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "value" BOOLEAN NOT NULL, PRIMARY KEY ("parent", "name", "namespace", "index"));
CREATE TABLE "namemultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "value" VARCHAR(255) NOT NULL, "valuenamespace" VARCHAR(255) NOT NULL DEFAULT '', PRIMARY KEY ("parent", "name", "namespace", "index"));
CREATE TABLE "pathmultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "level" INTEGER NOT NULL, "value" TEXT NOT NULL, "valuenamespace" VARCHAR(255) NOT NULL DEFAULT '', PRIMARY KEY ("parent", "name", "namespace", "index", "level"));
CREATE TABLE "referencemultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "value" VARCHAR(36) NOT NULL, PRIMARY KEY ("parent", "name", "namespace", "index"));
CREATE TABLE "weakreferencemultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "value" VARCHAR(36) NOT NULL, PRIMARY KEY ("parent", "name", "namespace", "index"));
CREATE TABLE "urimultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" VARCHAR(255) NOT NULL, "namespace" VARCHAR(255) NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "value" TEXT NOT NULL, PRIMARY KEY ("parent", "name", "namespace", "index"));

COMMIT;

