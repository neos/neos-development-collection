<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\DBALException;
use Doctrine\DBAL\Migrations\AbortMigrationException;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Fix event table schema
 */
class Version20200721124436 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Update neos_contentrepository_events schema';
    }

    /**
     * @param Schema $schema
     * @throws DBALException | AbortMigrationException
     */
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE neos_contentrepository_events CHANGE recordedat recordedat DATE NOT NULL COMMENT \'(DC2Type:date_immutable)\'');
        $this->addSql('CREATE INDEX IDX_67A26AC9EE6C504 ON neos_contentrepository_events (correlationidentifier)');
    }

    /**
     * @param Schema $schema
     * @throws DBALException | AbortMigrationException
     */
    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE neos_contentrepository_events CHANGE recordedat recordedat DATETIME NOT NULL');
        $this->addSql('DROP INDEX IDX_67A26AC9EE6C504 ON neos_contentrepository_events');
    }
}
