<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20190108151336 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove "hidden" flag in DB';
    }

    public function up(Schema $schema): void 
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );
        $this->addSql('ALTER TABLE neos_contentgraph_node DROP hidden');
    }

    public function down(Schema $schema): void 
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );

        $this->addSql('ALTER TABLE neos_contentgraph_node ADD hidden TINYINT(1) NULL');
    }
}
