BEGIN;

CREATE TABLE "namespaces" (
  "prefix" VARCHAR(255) PRIMARY KEY NOT NULL,
  "uri" TEXT NOT NULL,
  UNIQUE("uri"(255))
);

CREATE TABLE "nodetypes" (
  "name" TEXT NOT NULL,
  "namespace" TEXT NOT NULL DEFAULT '',
  PRIMARY KEY ("name"(127),"namespace"(127))
);

CREATE TABLE "nodes" (
  "identifier" VARCHAR(36) PRIMARY KEY NOT NULL,
  "name" TEXT NOT NULL,
  "namespace" TEXT NOT NULL DEFAULT '',
  "parent" VARCHAR(36) NOT NULL,
  "nodetype" TEXT,
  "nodetypenamespace" TEXT NOT NULL DEFAULT ''
);

CREATE TABLE "properties" (
  "parent" VARCHAR(36) NOT NULL,
  "name" TEXT NOT NULL,
  "namespace" TEXT NOT NULL DEFAULT '',
  "multivalue" BOOLEAN NOT NULL DEFAULT '0',
  "type" INTEGER NOT NULL DEFAULT 0,
  PRIMARY KEY ("parent", "name"(109), "namespace"(109))
);

CREATE TABLE "stringproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL DEFAULT 0, "value" TEXT NOT NULL, PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index"));
CREATE TABLE "binaryproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL DEFAULT 0, "value" TEXT NOT NULL, PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index"));
CREATE TABLE "longproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL DEFAULT 0, "value" INTEGER NOT NULL, PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index"));
CREATE TABLE "doubleproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL DEFAULT 0, "value" FLOAT NOT NULL, PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index"));
CREATE TABLE "decimalproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL DEFAULT 0, "value" DECIMAL NOT NULL, PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index"));
CREATE TABLE "dateproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL DEFAULT 0, "value" TIMESTAMP NOT NULL, PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index"));
CREATE TABLE "booleanproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL DEFAULT 0, "value" BOOLEAN NOT NULL, PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index"));
CREATE TABLE "nameproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL DEFAULT 0, "value" TEXT NOT NULL, "valuenamespace" TEXT NOT NULL DEFAULT '', PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index"));
CREATE TABLE "pathproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL DEFAULT 0,"level" INTEGER NOT NULL, "value" TEXT NOT NULL, "valuenamespace" TEXT NOT NULL DEFAULT '', PRIMARY KEY ("parent", "name"(100), "namespace"(100), "level", "index"));
CREATE TABLE "referenceproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL DEFAULT 0, "value" VARCHAR(36) NOT NULL, PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index"));
CREATE TABLE "weakreferenceproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL DEFAULT 0, "value" VARCHAR(36) NOT NULL, PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index"));
CREATE TABLE "uriproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL DEFAULT 0, "value" TEXT NOT NULL, PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index"));

CREATE TABLE "index_properties" (
  "parent" VARCHAR(36) NOT NULL,
  "name" TEXT NOT NULL,
  "namespace" TEXT NOT NULL,
  "type" INTEGER NOT NULL,
  "value" TEXT NOT NULL
  KEY "name" ("name"(255), "namespace"(255), "value"(255))
);

COMMIT;

