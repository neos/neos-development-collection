<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * The migration for providing nodes and relation hyperedges
 */
class Version20210314010646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'The migration for providing nodes and relation hyperedges';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'postgresql',
            'Migration can only be executed safely on "postgresql".'
        );

        $this->createNodeSchema();
        $this->createHierarchySchema();
        $this->createRestrictionSchema();
        $this->createReferenceSchema();
    }

    private function createNodeSchema(): void
    {
        $this->addSql(/** @lang PostgreSQL */'CREATE TABLE neos_contentgraph_node(
    relationanchorpoint uuid NOT NULL PRIMARY KEY,
    origindimensionspacepoint json NOT NULL,
    origindimensionspacepointhash varchar(255) NOT NULL,
    nodeaggregateidentifier varchar(255) NOT NULL,
    nodetypename varchar(255) NOT NULL,
    classification varchar(255) NOT NULL,
    properties jsonb NOT NULL,
    nodename varchar(255) NULL
)');
        $this->addSql('CREATE INDEX NODE_ORIGIN ON neos_contentgraph_node (origindimensionspacepointhash);');
        $this->addSql('CREATE INDEX NODE_AGGREGATE_IDENTIFIER ON neos_contentgraph_node (nodeaggregateidentifier);');
        $this->addSql(/** @lang PostgreSQL */'CREATE INDEX NODE_PROPERTIES ON neos_contentgraph_node USING GIN (properties);');
        $this->addSql('CREATE INDEX NODE_NAME ON neos_contentgraph_node (nodename);');
    }

    private function createHierarchySchema(): void
    {
        $this->addSql(/** @lang PostgreSQL */'CREATE TABLE neos_contentgraph_hierarchyhyperrelation(
    contentstreamidentifier varchar(255) NOT NULL,
    parentnodeanchor uuid NOT NULL,
    dimensionspacepoint json NOT NULL,
    dimensionspacepointhash varchar(255) NOT NULL,
    childnodeanchors uuid[] NOT NULL,
    PRIMARY KEY(contentstreamidentifier, parentnodeanchor, dimensionspacepointhash)
)');
        $this->addSql('CREATE INDEX HIERARCHY_CONTENT_STREAM_IDENTIFIER ON neos_contentgraph_hierarchyhyperrelation (contentstreamidentifier);');
        $this->addSql('CREATE INDEX HIERARCHY_PARENT ON neos_contentgraph_hierarchyhyperrelation (parentnodeanchor);');
        $this->addSql('CREATE INDEX HIERARCHY_DIMENSION_SPACE_POINT ON neos_contentgraph_hierarchyhyperrelation (dimensionspacepointhash);');
        $this->addSql(/** @lang PostgreSQL */'CREATE INDEX HIERARCHY_CHILDREN ON neos_contentgraph_hierarchyhyperrelation USING GIN (childnodeanchors);');
    }

    private function createRestrictionSchema(): void
    {
        $this->addSql(/** @lang PostgreSQL */'CREATE TABLE neos_contentgraph_restrictionhyperrelation(
    contentstreamidentifier varchar(255) NOT NULL,
    dimensionspacepointhash varchar(255) NOT NULL,
    originnodeaggregateidentifier varchar(255) NOT NULL,
    affectednodeaggregateidentifiers varchar(255)[] NOT NULL,
    PRIMARY KEY(contentstreamidentifier, dimensionspacepointhash, originnodeaggregateidentifier)
)');
        $this->addSql('CREATE INDEX RESTRICTION_CONTENT_STREAM_IDENTIFIER ON neos_contentgraph_restrictionhyperrelation (contentstreamidentifier);');
        $this->addSql('CREATE INDEX RESTRICTION_DIMENSION_SPACE_POINT ON neos_contentgraph_restrictionhyperrelation (dimensionspacepointhash);');
        $this->addSql('CREATE INDEX RESTRICTION_ORIGIN ON neos_contentgraph_restrictionhyperrelation (originnodeaggregateidentifier);');
        $this->addSql(/** @lang PostgreSQL */'CREATE INDEX RESTRICTION_AFFECTED ON neos_contentgraph_restrictionhyperrelation USING GIN (affectednodeaggregateidentifiers);');
    }

    private function createReferenceSchema(): void
    {
        $this->addSql(/** @lang PostgreSQL */'CREATE TABLE neos_contentgraph_referencerelation(
    sourcenodeanchor uuid NOT NULL,
    name varchar(255) NOT NULL,
    position smallint NOT NULL,
    properties jsonb NULL,
    targetnodeaggregateidentifier varchar(255) NOT NULL,
    PRIMARY KEY(sourcenodeanchor, name, position)
)');
        $this->addSql('CREATE INDEX REFERENCE_SOURCE ON neos_contentgraph_referencerelation (sourcenodeanchor);');
        $this->addSql(/** @lang PostgreSQL */'CREATE INDEX REFERENCE_TARGET ON neos_contentgraph_referencerelation (targetnodeaggregateidentifier);');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'postgresql',
            'Migration can only be executed safely on "postgresql".'
        );

        $this->addSql('DROP TABLE IF EXISTS neos_contentgraph_node');
        $this->addSql('DROP TABLE IF EXISTS neos_contentgraph_hierarchyhyperrelation');
        $this->addSql('DROP TABLE IF EXISTS neos_contentgraph_restrictionhyperrelation');
        $this->addSql('DROP TABLE IF EXISTS neos_contentgraph_referencerelation');
    }
}
