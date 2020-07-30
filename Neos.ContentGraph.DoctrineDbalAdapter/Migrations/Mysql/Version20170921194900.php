<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170921194900 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'The migration for splitting subgraph identity hashes';
    }

    public function up(Schema $schema)
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );

        $this->addSql('ALTER TABLE neos_contentgraph_node DROP PRIMARY KEY');
        $this->addSql('DROP INDEX IDENTIFIER_IN_GRAPH ON neos_contentgraph_node');

        $this->addSql('ALTER TABLE neos_contentgraph_node DROP contentstreamidentifier');
        $this->addSql('ALTER TABLE neos_contentgraph_node ADD relationanchorpoint VARCHAR(255) NOT NULL');

        $this->addSql('ALTER TABLE neos_contentgraph_node ADD PRIMARY KEY (relationanchorpoint)');
        $this->addSql('CREATE INDEX NODE_IDENTIFIER ON neos_contentgraph_node (nodeidentifier)');
        $this->addSql('CREATE INDEX NODE_AGGREGATE_IDENTIFIER ON neos_contentgraph_node (nodeaggregateidentifier)');
        $this->addSql('CREATE INDEX NODE_TYPE_NAME ON neos_contentgraph_node (nodetypename)');


        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation DROP PRIMARY KEY');

        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation ADD parentnodeanchor VARCHAR(255) NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation ADD childnodeanchor VARCHAR(255) NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation CHANGE dimensionspacepoint dimensionspacepoint TEXT NOT NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation DROP parentnodeidentifier');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation DROP childnodeidentifier');

        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation ADD PRIMARY KEY (parentnodeanchor, contentstreamidentifier, dimensionspacepointhash, childnodeanchor)');
        $this->addSql('CREATE INDEX SUBGRAPH_IDENTIFIER ON neos_contentgraph_hierarchyrelation (contentstreamidentifier, dimensionspacepointhash)');
    }

    public function down(Schema $schema)
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );

        $this->addSql('DROP INDEX SUBGRAPH_IDENTIFIER ON neos_contentgraph_hierarchyrelation');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation DROP PRIMARY KEY');

        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation ADD childnodeidentifier VARCHAR(255) NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation ADD parentnodeidentifier VARCHAR(255) NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation CHANGE dimensionspacepoint dimensionspacepoint TEXT NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation DROP childnodeanchor');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation DROP parentnodeanchor');

        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation ADD PRIMARY KEY (parentnodeidentifier, contentstreamidentifier, dimensionspacepointhash, childnodeidentifier)');


        $this->addSql('DROP INDEX NODE_TYPE_NAME ON neos_contentgraph_node');
        $this->addSql('DROP INDEX NODE_AGGREGATE_IDENTIFIER ON neos_contentgraph_node');
        $this->addSql('DROP INDEX NODE_IDENTIFIER ON neos_contentgraph_node');
        $this->addSql('ALTER TABLE neos_contentgraph_node DROP PRIMARY KEY');

        $this->addSql('ALTER TABLE neos_contentgraph_node DROP relationanchorpoint');
        $this->addSql('ALTER TABLE neos_contentgraph_node ADD contentstreamidentifier VARCHAR(255) NULL');

        $this->addSql('CREATE UNIQUE INDEX IDENTIFIER_IN_GRAPH ON neos_contentgraph_node (nodeaggregateidentifier, contentstreamidentifier, dimensionspacepointhash)');
        $this->addSql('ALTER TABLE neos_contentgraph_node ADD PRIMARY KEY (nodeidentifier)');
    }
}
