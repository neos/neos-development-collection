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
CREATE TABLE "stringproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "value" TEXT NOT NULL, PRIMARY KEY ("parent", "name"(109), "namespace"(109)));
CREATE TABLE "binaryproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "value" TEXT NOT NULL, PRIMARY KEY ("parent", "name"(109), "namespace"(109)));
CREATE TABLE "longproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "value" INTEGER NOT NULL, PRIMARY KEY ("parent", "name"(109), "namespace"(109)));
CREATE TABLE "doubleproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "value" FLOAT NOT NULL, PRIMARY KEY ("parent", "name"(109), "namespace"(109)));
CREATE TABLE "decimalproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "value" DECIMAL NOT NULL, PRIMARY KEY ("parent", "name"(109), "namespace"(109)));
CREATE TABLE "dateproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "value" TIMESTAMP NOT NULL, PRIMARY KEY ("parent", "name"(109), "namespace"(109)));
CREATE TABLE "booleanproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "value" BOOLEAN NOT NULL, PRIMARY KEY ("parent", "name"(109), "namespace"(109)));
CREATE TABLE "nameproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "value" TEXT NOT NULL, "valuenamespace" TEXT NOT NULL DEFAULT '', PRIMARY KEY ("parent", "name"(109), "namespace"(109)));
CREATE TABLE "pathproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '',"level" INTEGER NOT NULL, "value" TEXT NOT NULL, "valuenamespace" TEXT NOT NULL DEFAULT '', PRIMARY KEY ("parent","name"(100),"namespace"(100),"level"));
CREATE TABLE "referenceproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "value" VARCHAR(36) NOT NULL, PRIMARY KEY ("parent", "name"(109), "namespace"(109)));
CREATE TABLE "weakreferenceproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "value" VARCHAR(36) NOT NULL, PRIMARY KEY ("parent", "name"(109), "namespace"(109)));
CREATE TABLE "uriproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "value" TEXT NOT NULL, PRIMARY KEY ("parent", "name"(109), "namespace"(109)));
CREATE TABLE "stringmultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "value" TEXT NOT NULL, PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index"));
CREATE TABLE "binarymultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "value" TEXT NOT NULL, PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index"));
CREATE TABLE "longmultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "value" INTEGER NOT NULL, PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index"));
CREATE TABLE "doublemultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "value" FLOAT NOT NULL, PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index"));
CREATE TABLE "decimalmultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "value" DECIMAL NOT NULL, PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index"));
CREATE TABLE "datemultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "value" TIMESTAMP NOT NULL, PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index"));
CREATE TABLE "booleanmultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "value" BOOLEAN NOT NULL, PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index"));
CREATE TABLE "namemultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "value" TEXT NOT NULL, "valuenamespace" TEXT NOT NULL DEFAULT '', PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index"));
CREATE TABLE "pathmultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "level" INTEGER NOT NULL, "value" TEXT NOT NULL, "valuenamespace" TEXT NOT NULL DEFAULT '', PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index", "level"));
CREATE TABLE "referencemultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "value" VARCHAR(36) NOT NULL, PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index"));
CREATE TABLE "weakreferencemultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "value" VARCHAR(36) NOT NULL, PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index"));
CREATE TABLE "urimultivalueproperties" ("parent" VARCHAR(36) NOT NULL, "name" TEXT NOT NULL, "namespace" TEXT NOT NULL DEFAULT '', "index" INTEGER NOT NULL, "value" TEXT NOT NULL, PRIMARY KEY ("parent", "name"(100), "namespace"(100), "index"));

COMMIT;

