<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Makes sure the base workspace is set to NULL on delete preventing foreign
 * key constraints while dropping all nodes / workspaces.
 */
class Version20121014005902 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP FOREIGN KEY typo3_typo3cr_domain_model_workspace_ibfk_1");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT FK_71DE9CFBE9BFE681 FOREIGN KEY (baseworkspace) REFERENCES typo3_typo3cr_domain_model_workspace (persistence_object_identifier) ON DELETE SET NULL");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP FOREIGN KEY FK_71DE9CFBE9BFE681");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT typo3_typo3cr_domain_model_workspace_ibfk_1 FOREIGN KEY (baseworkspace) REFERENCES typo3_typo3cr_domain_model_workspace (persistence_object_identifier)");
    }
}
