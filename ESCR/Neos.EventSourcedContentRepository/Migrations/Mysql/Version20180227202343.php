<?php
declare(strict_types=1);
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20180227202343 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'The migration for providing changes';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('CREATE TABLE neos_contentrepository_projection_change (contentStreamIdentifier VARCHAR(255) NOT NULL, nodeIdentifier VARCHAR(255) NOT NULL, changed tinyint(1) NOT NULL, moved tinyint(1) NOT NULL, PRIMARY KEY(contentStreamIdentifier, nodeIdentifier)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('DROP TABLE neos_contentrepository_projection_change');
    }
}
