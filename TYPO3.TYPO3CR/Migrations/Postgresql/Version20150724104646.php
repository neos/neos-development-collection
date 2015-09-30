<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add owner to workspaces
 */
class Version20150724104646 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD owner VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT FK_71DE9CFBCF60E67C FOREIGN KEY (owner) REFERENCES typo3_neos_domain_model_user (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("CREATE INDEX IDX_71DE9CFBCF60E67C ON typo3_typo3cr_domain_model_workspace (owner)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP CONSTRAINT FK_71DE9CFBCF60E67C");
        $this->addSql("DROP INDEX IDX_71DE9CFBCF60E67C");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP owner");
    }
}
