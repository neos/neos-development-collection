<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20190411184933 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add index on parent node anchor (speeds up performance for some queries a lot; e.g. with 50 000 nodes from 150 ms to <1 ms)';
    }

    public function up(Schema $schema): void 
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );
        $this->addSql('ALTER TABLE `neos_contentgraph_hierarchyrelation` ADD INDEX `PARENTNODEANCHOR` (`parentnodeanchor`)');
    }

    public function down(Schema $schema): void 
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );

        $this->addSql('ALTER TABLE `neos_contentgraph_hierarchyrelation` DROP INDEX `PARENTNODEANCHOR`');
    }
}
