<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170921110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'The migration for splitting subgraph identity hashes';
    }

    public function up(Schema $schema): void 
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );

        $this->addSql('DROP INDEX IDENTIFIER_IN_GRAPH ON neos_contentgraph_node');

        $this->addSql('ALTER TABLE neos_contentgraph_node DROP subgraphidentityhash');
        $this->addSql('ALTER TABLE neos_contentgraph_node DROP subgraphidentifier');
        $this->addSql('ALTER TABLE neos_contentgraph_node ADD contentstreamidentifier VARCHAR(255) NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_node ADD dimensionspacepoint TEXT NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_node ADD dimensionspacepointhash VARCHAR(255) NULL');

        $this->addSql('CREATE UNIQUE INDEX IDENTIFIER_IN_GRAPH ON neos_contentgraph_node (nodeaggregateidentifier, contentstreamidentifier, dimensionspacepointhash)');


        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation DROP PRIMARY KEY');

        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation DROP subgraphidentityhash');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation DROP subgraphidentifier');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation ADD contentstreamidentifier VARCHAR(255) NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation ADD dimensionspacepoint TEXT NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation ADD dimensionspacepointhash VARCHAR(255) NULL');

        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation ADD PRIMARY KEY (parentnodeidentifier, contentstreamidentifier, dimensionspacepointhash, childnodeidentifier)');
    }

    public function down(Schema $schema): void 
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );

        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation DROP PRIMARY KEY');

        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation DROP dimensionspacepointhash');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation DROP dimensionspacepoint');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation DROP contentstreamidentifier');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation ADD subgraphidentifier TEXT NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation ADD subgraphidentityhash VARCHAR(255) NOT NULL');

        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation ADD PRIMARY KEY (parentnodeidentifier, subgraphidentityhash, childnodeidentifier)');


        $this->addSql('DROP INDEX IDENTIFIER_IN_GRAPH ON neos_contentgraph_node');

        $this->addSql('ALTER TABLE neos_contentgraph_node DROP dimensionspacepointhash');
        $this->addSql('ALTER TABLE neos_contentgraph_node DROP dimensionspacepoint');
        $this->addSql('ALTER TABLE neos_contentgraph_node DROP contentstreamidentifier');
        $this->addSql('ALTER TABLE neos_contentgraph_node ADD subgraphidentifier TEXT NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_node ADD subgraphidentityhash VARCHAR(255) NULL');

        $this->addSql('CREATE UNIQUE INDEX IDENTIFIER_IN_GRAPH ON neos_contentgraph_node (nodeaggregateidentifier, subgraphidentityhash)');
    }
}
