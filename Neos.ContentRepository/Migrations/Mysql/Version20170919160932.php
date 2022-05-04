<?php
declare(strict_types=1);
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170919160932 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Introduce projection for workspaces';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');
        $this->addSql('CREATE TABLE neos_contentrepository_projection_workspace_v1 (persistence_object_identifier VARCHAR(40) NOT NULL, workspacename VARCHAR(255) NOT NULL, baseworkspacename VARCHAR(255) NOT NULL, workspacetitle VARCHAR(255) NOT NULL, workspacedescription VARCHAR(255) NOT NULL, workspaceowner VARCHAR(255) NOT NULL, PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');
        $this->addSql('DROP TABLE neos_contentrepository_projection_workspace_v1');
    }
}
