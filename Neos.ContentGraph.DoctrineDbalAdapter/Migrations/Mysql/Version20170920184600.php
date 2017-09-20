<?php

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Make nodeaggregateidentifier nullable
 */
class Version20170920184600 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'The migration for adjusting nodes to support node aggregates and unhashed subgraph identifiers';
    }

    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE neos_contentgraph_node CHANGE nodeaggregateidentifier nodeaggregateidentifier VARCHAR(255) NULL');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".');
        $this->addSql('ALTER TABLE neos_contentgraph_node CHANGE nodeaggregateidentifier nodeaggregateidentifier VARCHAR(255) NOT NULL');
    }
}
