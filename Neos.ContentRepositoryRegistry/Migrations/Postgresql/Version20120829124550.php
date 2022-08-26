<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add migration status table for Neos.ContentRepository
 */
class Version20120829124550 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("CREATE TABLE typo3_typo3cr_migration_domain_model_migrationstatus (flow3_persistence_identifier VARCHAR(40) NOT NULL, version VARCHAR(14) NOT NULL, workspacename VARCHAR(255) NOT NULL, direction VARCHAR(4) NOT NULL, applicationtimestamp TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(flow3_persistence_identifier))");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("DROP TABLE typo3_typo3cr_migration_domain_model_migrationstatus");
    }
}
