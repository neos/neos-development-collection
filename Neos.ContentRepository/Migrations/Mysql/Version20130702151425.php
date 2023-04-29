<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Node data refactoring
 */
class Version20130702151425 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP FOREIGN KEY typo3_typo3cr_domain_model_workspace_ibfk_2");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node RENAME typo3_typo3cr_domain_model_nodedata");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT FK_71DE9CFBA762B951 FOREIGN KEY (rootnode) REFERENCES typo3_typo3cr_domain_model_nodedata (persistence_object_identifier)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP FOREIGN KEY FK_71DE9CFBA762B951");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata RENAME typo3_typo3cr_domain_model_node");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT typo3_typo3cr_domain_model_workspace_ibfk_2 FOREIGN KEY (rootnode) REFERENCES typo3_typo3cr_domain_model_node (persistence_object_identifier)");
    }
}
