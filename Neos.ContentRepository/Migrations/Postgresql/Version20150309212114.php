<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust DB schema to a clean state (remove cruft that built up in the past)
 */
class Version20150309212114 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER pathhash DROP DEFAULT");
        $this->addSql("ALTER INDEX IF EXISTS idx_820cadc88d940019 RENAME TO IDX_60A956B98D940019"); // typo3_typo3cr_domain_model_nodedata
        $this->addSql("ALTER INDEX IF EXISTS idx_820cadc84930c33c RENAME TO IDX_60A956B94930C33C"); // typo3_typo3cr_domain_model_nodedata
        $this->addSql("ALTER INDEX IF EXISTS idx_71de9cfba762b951 RENAME TO IDX_71DE9CFBBB46155"); // typo3_typo3cr_domain_model_workspace
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER pathhash SET DEFAULT ''");
        $this->addSql("ALTER INDEX IF EXISTS idx_60a956b98d940019 RENAME TO idx_820cadc88d940019"); // typo3_typo3cr_domain_model_nodedata
        $this->addSql("ALTER INDEX IF EXISTS idx_60a956b94930c33c RENAME TO idx_820cadc84930c33c"); // typo3_typo3cr_domain_model_nodedata
        $this->addSql("ALTER INDEX IF EXISTS idx_71de9cfbbb46155 RENAME TO idx_71de9cfba762b951"); // typo3_typo3cr_domain_model_workspace
    }
}
