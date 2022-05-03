<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20190108141053 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create neos_contentrepository_projection_nodehiddenstate table';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('CREATE TABLE neos_contentrepository_projection_nodehiddenstate (contentstreamidentifier VARCHAR(255) NOT NULL, nodeaggregateidentifier VARCHAR(255) NOT NULL, dimensionspacepointhash VARCHAR(255) NOT NULL, dimensionspacepoint TEXT, hidden TINYINT(1), PRIMARY KEY(contentstreamidentifier, nodeaggregateidentifier, dimensionspacepointhash)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP TABLE neos_contentrepository_projection_nodehiddenstate');
    }
}
