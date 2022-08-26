<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Create unique indexes for identity properties and make version non-nullable
 */
class Version20120429213447 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node CHANGE version version INT DEFAULT 1 NOT NULL");
        $this->addSql("CREATE UNIQUE INDEX flow3_identity_typo3_typo3cr_domain_model_workspace ON typo3_typo3cr_domain_model_workspace (name)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node CHANGE version version INT NOT NULL");
        $this->addSql("DROP INDEX flow3_identity_typo3_typo3cr_domain_model_workspace ON typo3_typo3cr_domain_model_workspace");
    }
}
