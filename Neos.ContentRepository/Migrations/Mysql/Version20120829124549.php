<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add migration status table for Neos.ContentRepository
 */
class Version20120829124549 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("CREATE TABLE typo3_typo3cr_migration_domain_model_migrationstatus (flow3_persistence_identifier VARCHAR(40) NOT NULL, version VARCHAR(14) NOT NULL, workspacename VARCHAR(255) NOT NULL, direction VARCHAR(4) NOT NULL, applicationtimestamp DATETIME NOT NULL, PRIMARY KEY(flow3_persistence_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("DROP TABLE typo3_typo3cr_migration_domain_model_migrationstatus");
    }
}
