<?php
declare(strict_types=1);
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170920163431 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make the workspace name the identifier of the workspace read model';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE neos_contentrepository_projection_workspace_v1 DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE neos_contentrepository_projection_workspace_v1 DROP persistence_object_identifier');
        $this->addSql('ALTER TABLE neos_contentrepository_projection_workspace_v1 ADD PRIMARY KEY (workspacename)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE neos_contentrepository_projection_workspace_v1 DROP PRIMARY KEY');
        $this->addSql('ALTER TABLE neos_contentrepository_projection_workspace_v1 ADD persistence_object_identifier VARCHAR(40) NOT NULL COLLATE utf8_unicode_ci');
        $this->addSql('ALTER TABLE neos_contentrepository_projection_workspace_v1 ADD PRIMARY KEY (persistence_object_identifier)');
    }
}
