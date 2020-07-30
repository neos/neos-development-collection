<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * The migration for providing nodes and hierarchy edges
 */
class Version20170920184500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'The migration for adjusting nodes to support node aggregates and unhashed subgraph identifiers';
    }

    public function up(Schema $schema)
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );

        $this->addSql('ALTER TABLE neos_contentgraph_node DROP PRIMARY KEY');

        $this->addSql('ALTER TABLE neos_contentgraph_node ADD nodeaggregateidentifier VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_node ADD subgraphidentityhash VARCHAR(255) NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_node CHANGE subgraphidentifier subgraphidentifier TEXT NULL');

        $this->addSql('ALTER TABLE neos_contentgraph_node ADD PRIMARY KEY (nodeidentifier)');
        $this->addSql('CREATE UNIQUE INDEX IDENTIFIER_IN_GRAPH ON neos_contentgraph_node (nodeaggregateidentifier, subgraphidentityhash)');


        $this->addSql('RENAME TABLE neos_contentgraph_hierarchyedge TO neos_contentgraph_hierarchyrelation');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation DROP PRIMARY KEY');

        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation ADD subgraphidentityhash VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation CHANGE subgraphidentifier subgraphidentifier TEXT NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation CHANGE parentnodesidentifieringraph parentnodeidentifier VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation CHANGE childnodesidentifieringraph childnodeidentifier VARCHAR(255) NOT NULL');

        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation ADD PRIMARY KEY (parentnodeidentifier, subgraphidentityhash, childnodeidentifier)');
    }

    public function down(Schema $schema)
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation DROP PRIMARY KEY');

        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation CHANGE childnodeidentifier childnodesidentifieringraph VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation CHANGE parentnodeidentifier parentnodesidentifieringraph VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation CHANGE subgraphidentifier subgraphidentifier VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation DROP subgraphidentityhash');

        $this->addSql('ALTER TABLE neos_contentgraph_hierarchyrelation ADD PRIMARY KEY (parentnodesidentifieringraph, subgraphidentifier, childnodesidentifieringraph)');
        $this->addSql('RENAME TABLE neos_contentgraph_hierarchyrelation TO neos_contentgraph_hierarchyedge');


        $this->addSql('DROP INDEX IDENTIFIER_IN_GRAPH ON neos_contentgraph_node');
        $this->addSql('ALTER TABLE neos_contentgraph_node DROP PRIMARY KEY');

        $this->addSql('ALTER TABLE neos_contentgraph_node CHANGE subgraphidentifier subgraphidentifier VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE neos_contentgraph_node DROP subgraphidentityhash');
        $this->addSql('ALTER TABLE neos_contentgraph_node DROP nodeaggregateidentifier');

        $this->addSql('ALTER TABLE neos_contentgraph_node ADD PRIMARY KEY (nodeidentifier, subgraphidentifier)');
    }
}
