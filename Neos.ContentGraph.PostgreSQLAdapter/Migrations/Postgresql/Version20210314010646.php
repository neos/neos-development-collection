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

        $this->addSql(/** @lang PostgreSQL */'CREATE TABLE neos_contentgraph_node(
    relationanchorpoint varchar(255) NOT NULL PRIMARY KEY,
    origindimensionspacepoint jsonb NOT NULL,
    origindimensionspacepointhash varchar(255) NOT NULL,
    nodeaggregateidentifier varchar(255) NOT NULL,
    nodetypename varchar(255) NOT NULL,
    classification varchar(255) NOT NULL,
    properties jsonb NOT NULL,
    nodename varchar(255) NULL
)');

        $this->addSql('CREATE INDEX NODE_AGGREGATE_IDENTIFIER ON neos_contentgraph_node (nodeaggregateidentifier);');
        $this->addSql(/** @lang PostgreSQL */'CREATE INDEX PROPERTIES ON neos_contentgraph_node USING GIN (properties);');

        $this->addSql(/** @lang PostgreSQL */'CREATE TABLE neos_contentgraph_hierarchyhyperrelation(
    contentstreamidentifier varchar(255) NOT NULL,
    parentnodeanchor varchar(255) NOT NULL,
    dimensionspacepoint json NOT NULL,
    dimensionspacepointhash varchar(255) NOT NULL,
    childnodeanchors jsonb NOT NULL
)');
        $this->addSql('CREATE INDEX CONTENT_STREAM_IDENTIFIER ON neos_contentgraph_hierarchyhyperrelation (contentstreamidentifier);');
        $this->addSql('CREATE INDEX PARENT_NODE_ANCHOR ON neos_contentgraph_hierarchyhyperrelation (parentnodeanchor);');
        $this->addSql('CREATE INDEX DIMENSION_SPACE_POINT ON neos_contentgraph_hierarchyhyperrelation (dimensionspacepointhash);');
        $this->addSql(/** @lang PostgreSQL */'CREATE INDEX CHILD_NODE_ANCHORS ON neos_contentgraph_hierarchyhyperrelation USING GIN (childnodeanchors);');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'postgresql',
            'Migration can only be executed safely on "postgresql".'
        );

        $this->addSql('DROP TABLE neos_contentgraph_node');
        $this->addSql('DROP TABLE neos_contentgraph_hierarchyhyperrelation');
    }
}
