<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * The migration for providing nodes and relation edges
 */
class Version20210314010646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'The migration for providing nodes and relation edges';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'postgresql',
            'Migration can only be executed safely on "postgresql".'
        );

        $this->addSql('CREATE TABLE neos_contentgraph_node(
    relationanchorpoint uuid NOT NULL PRIMARY KEY,
    origindimensionspacepoint jsonb NOT NULL,
    origindimensionspacepointhash varchar(255) NOT NULL,
    nodeaggregateidentifier varchar(255) NOT NULL,
    nodetypename varchar(255) NOT NULL,
    classification varchar(255) NOT NULL,
    properties jsonb NOT NULL
)');

        $this->addSql('CREATE INDEX NODE_AGGREGATE_IDENTIFIER ON neos_contentgraph_node (nodeaggregateidentifier);');
        $this->addSql('CREATE INDEX PROPERTIES ON neos_contentgraph_node USING GIN (properties);');

        $this->addSql('CREATE TABLE neos_contentgraph_hierarchyrelationset(
    contentstreamidentifier varchar(255) NOT NULL,
    parentnodeanchor uuid NOT NULL,
    childnodeanchorpoints jsonb NOT NULL
)');
        $this->addSql('CREATE INDEX CONTENT_STREAM_IDENTIFIER ON neos_contentgraph_hierarchyrelationset (contentstreamidentifier);');
        $this->addSql('CREATE INDEX PARENT_NODE_ANCHOR ON neos_contentgraph_hierarchyrelationset (parentnodeanchor);');
        $this->addSql('CREATE INDEX CHILD_NODE_ANCHOR_POINTS ON neos_contentgraph_hierarchyrelationset USING GIN (childnodeanchorpoints);');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'postgresql',
            'Migration can only be executed safely on "postgresql".'
        );

        $this->addSql('DROP TABLE neos_contentgraph_node');
        $this->addSql('DROP TABLE neos_contentgraph_hierarchyrelationset');
    }
}
