BEGIN;

CREATE TABLE "namespaces" (
	"prefix" VARCHAR(255) PRIMARY KEY NOT NULL,
	"uri" TEXT UNIQUE NOT NULL
);

CREATE TABLE "nodetypes" (
	"name" VARCHAR(255) PRIMARY KEY NOT NULL
);
INSERT INTO "nodetypes" ("name") VALUES ('nt:base');
INSERT INTO "nodetypes" ("name") VALUES ('nt:unstructured');

CREATE TABLE "nodes" (
  "identifier" VARCHAR(36) PRIMARY KEY NOT NULL,
  "name" VARCHAR(255) NOT NULL,
  "parent" VARCHAR(36) NOT NULL,
  "nodetype" VARCHAR(255)
);
INSERT INTO "nodes" ("identifier", "name", "parent", "nodetype") VALUES ('96b4a35d-1ef5-4a47-8b3c-0d6d69507e01', '', '', 'nt:base');

CREATE TABLE "properties" (
  "parent" VARCHAR(36) NOT NULL,
  "name" VARCHAR(255) NOT NULL,
  "value" TEXT NOT NULL,
  "namespace" VARCHAR(255) NOT NULL DEFAULT '',
  "multivalue" BOOLEAN NOT NULL DEFAULT '0',
  "type" INTEGER NOT NULL DEFAULT 0
);

COMMIT;
