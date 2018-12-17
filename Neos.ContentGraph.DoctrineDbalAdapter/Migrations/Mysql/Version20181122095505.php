<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20181122095505 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create restriction edge';
    }

    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".');
        $this->addSql('
        CREATE TABLE neos_contentgraph_restrictionedge (
                contentstreamidentifier VARCHAR(255) NOT NULL,
                dimensionspacepointhash VARCHAR(255) NOT NULL,
                originnodeaggregateidentifier VARCHAR(255) NOT NULL,
                affectednodeaggregateidentifier VARCHAR(255) NOT NULL,
                PRIMARY KEY(contentstreamidentifier, dimensionspacepointhash, originnodeaggregateidentifier, affectednodeaggregateidentifier)
            ) 
            DEFAULT CHARACTER SET utf8 
            COLLATE utf8_unicode_ci ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP TABLE neos_contentgraph_restrictionedge');
    }
}
