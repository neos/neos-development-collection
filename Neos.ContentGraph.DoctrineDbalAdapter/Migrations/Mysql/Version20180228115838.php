<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180228115838 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create neos_contentgraph_referencerelation table';
    }

    public function up(Schema $schema)
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );
        $this->addSql('
            CREATE TABLE neos_contentgraph_referencerelation (
                name VARCHAR(255) NOT NULL,
                position INT(11) NOT NULL,
                nodeanchorpoint VARCHAR(255) NOT NULL,
                destinationnodeaggregateidentifier VARCHAR(255) NOT NULL,
                PRIMARY KEY(name, position, nodeanchorpoint)
            ) 
            DEFAULT CHARACTER SET utf8 
            COLLATE utf8_unicode_ci ENGINE = InnoDB
        ');
    }

    public function down(Schema $schema)
    {
        $this->abortIf(
            $this->connection->getDatabasePlatform()->getName() != 'mysql',
            'Migration can only be executed safely on "mysql".'
        );

        $this->addSql('DROP TABLE neos_contentgraph_referencerelation');
    }
}
