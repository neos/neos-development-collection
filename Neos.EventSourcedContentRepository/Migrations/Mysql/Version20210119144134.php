<?php
declare(strict_types=1);
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210119144134 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Update neos_contentrepository_events schema (recorded at => DateTime)';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE neos_contentrepository_events CHANGE recordedat recordedat DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE neos_contentrepository_events CHANGE recordedat recordedat DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\'');
    }
}
