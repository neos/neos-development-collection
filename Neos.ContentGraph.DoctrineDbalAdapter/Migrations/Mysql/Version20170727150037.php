<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * The migration for providing nodes and hierarchy edges
 */
class Version20170727150037 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'The migration for providing nodes and hierarchy edges';
    }

    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".');

        $this->addSql('CREATE TABLE neos_arboretum_hierarchyedge (parentnodesidentifieringraph VARCHAR(255) NOT NULL, childnodesidentifieringraph VARCHAR(255) NOT NULL, name VARCHAR(255) NULL, subgraphidentifier VARCHAR(255) NOT NULL, position INT NOT NULL, PRIMARY KEY(parentnodesidentifieringraph, subgraphidentifier, childnodesidentifieringraph)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neos_arboretum_node (identifieringraph VARCHAR(255) NOT NULL, identifierinsubgraph VARCHAR(255) NOT NULL, subgraphidentifier VARCHAR(255) NOT NULL, properties LONGTEXT NOT NULL COMMENT \'(DC2Type:flow_json_array)\', nodetypename VARCHAR(255) NOT NULL, PRIMARY KEY(identifieringraph)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP TABLE neos_arboretum_hierarchyedge');
        $this->addSql('DROP TABLE neos_arboretum_node');
    }
}
