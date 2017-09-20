<?php

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * The migration for providing nodes and hierarchy edges
 */
class Version20170919234100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'The migration for adjusting nodes to the new identifier format';
    }

    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE neos_contentgraph_node DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE neos_contentgraph_node DROP identifieringraph');
        $this->addSql('ALTER TABLE neos_contentgraph_node DROP identifierinsubgraph');
        $this->addSql('ALTER TABLE neos_contentgraph_node ADD nodeidentifier VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_node ADD PRIMARY KEY (nodeidentifier, subgraphidentifier)');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE neos_contentgraph_node DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE neos_contentgraph_node DROP nodeidentifier');
        $this->addSql('ALTER TABLE neos_contentgraph_node ADD identifieringraph VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_node ADD identifierinsubgraph VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_node ADD PRIMARY KEY (identifieringraph)');
    }
}
