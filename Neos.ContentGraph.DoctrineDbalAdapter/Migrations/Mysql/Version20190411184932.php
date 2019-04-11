<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20190411184932 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index on child node anchor (speeds up performance for many queries a lot; e.g. with 50 000 nodes from 800 ms to <1 ms)';
    }

    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".');
        $this->addSql('ALTER TABLE `neos_contentgraph_hierarchyrelation` ADD INDEX `CHILDNODEANCHOR` (`childnodeanchor`)');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE `neos_contentgraph_hierarchyrelation` DROP INDEX `CHILDNODEANCHOR`');
    }
}
