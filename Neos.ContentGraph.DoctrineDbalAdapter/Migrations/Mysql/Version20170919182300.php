<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * The migration for providing nodes and hierarchy edges
 */
class Version20170919182300 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'The migration for adjusting table names to the new namespace';
    }

    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".');

        $this->addSql('RENAME TABLE neos_arboretum_node TO neos_contentgraph_node');
        $this->addSql('RENAME TABLE neos_arboretum_hierarchyedge TO neos_contentgraph_hierarchyedge');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".');

        $this->addSql('RENAME TABLE neos_contentgraph_node TO neos_arboretum_node');
        $this->addSql('RENAME TABLE neos_contentgraph_hierarchyedge TO neos_arboretum_hierarchyedge');
    }
}
