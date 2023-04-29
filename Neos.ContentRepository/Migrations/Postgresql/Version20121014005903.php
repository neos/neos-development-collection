<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Makes sure the base workspace is set to NULL on delete preventing foreign
 * key constraints while dropping all nodes / workspaces.
 */
class Version20121014005903 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP CONSTRAINT FK_71DE9CFBE9BFE681");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT FK_71DE9CFBE9BFE681 FOREIGN KEY (baseworkspace) REFERENCES typo3_typo3cr_domain_model_workspace (persistence_object_identifier) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP CONSTRAINT fk_71de9cfbe9bfe681");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT fk_71de9cfbe9bfe681 FOREIGN KEY (baseworkspace) REFERENCES typo3_typo3cr_domain_model_workspace (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
    }
}
