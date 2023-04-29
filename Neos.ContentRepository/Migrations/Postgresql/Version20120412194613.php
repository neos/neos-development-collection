<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Create tables for PostgreSQL
 */
class Version20120412194613 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("CREATE TABLE typo3_typo3cr_domain_model_contentobjectproxy (flow3_persistence_identifier VARCHAR(40) NOT NULL, targettype VARCHAR(255) NOT NULL, targetid VARCHAR(255) NOT NULL, PRIMARY KEY(flow3_persistence_identifier))");
        $this->addSql("CREATE TABLE typo3_typo3cr_domain_model_node (flow3_persistence_identifier VARCHAR(40) NOT NULL, workspace VARCHAR(40) DEFAULT NULL, contentobjectproxy VARCHAR(40) DEFAULT NULL, version INT DEFAULT 1 NOT NULL, path VARCHAR(255) NOT NULL, parentpath VARCHAR(255) NOT NULL, identifier VARCHAR(255) NOT NULL, sortingindex INT DEFAULT NULL, properties TEXT NOT NULL, contenttype VARCHAR(255) NOT NULL, removed BOOLEAN NOT NULL, hidden BOOLEAN NOT NULL, hiddenbeforedatetime TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, hiddenafterdatetime TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, hiddeninindex BOOLEAN NOT NULL, accessroles TEXT NOT NULL, PRIMARY KEY(flow3_persistence_identifier))");
        $this->addSql("CREATE INDEX IDX_820CADC88D940019 ON typo3_typo3cr_domain_model_node (workspace)");
        $this->addSql("CREATE INDEX IDX_820CADC84930C33C ON typo3_typo3cr_domain_model_node (contentobjectproxy)");
        $this->addSql("COMMENT ON COLUMN typo3_typo3cr_domain_model_node.properties IS '(DC2Type:array)'");
        $this->addSql("COMMENT ON COLUMN typo3_typo3cr_domain_model_node.accessroles IS '(DC2Type:array)'");
        $this->addSql("CREATE TABLE typo3_typo3cr_domain_model_workspace (flow3_persistence_identifier VARCHAR(40) NOT NULL, baseworkspace VARCHAR(40) DEFAULT NULL, rootnode VARCHAR(40) DEFAULT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(flow3_persistence_identifier))");
        $this->addSql("CREATE INDEX IDX_71DE9CFBE9BFE681 ON typo3_typo3cr_domain_model_workspace (baseworkspace)");
        $this->addSql("CREATE INDEX IDX_71DE9CFBA762B951 ON typo3_typo3cr_domain_model_workspace (rootnode)");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node ADD CONSTRAINT FK_820CADC88D940019 FOREIGN KEY (workspace) REFERENCES typo3_typo3cr_domain_model_workspace (flow3_persistence_identifier) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node ADD CONSTRAINT FK_820CADC84930C33C FOREIGN KEY (contentobjectproxy) REFERENCES typo3_typo3cr_domain_model_contentobjectproxy (flow3_persistence_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT FK_71DE9CFBE9BFE681 FOREIGN KEY (baseworkspace) REFERENCES typo3_typo3cr_domain_model_workspace (flow3_persistence_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT FK_71DE9CFBA762B951 FOREIGN KEY (rootnode) REFERENCES typo3_typo3cr_domain_model_node (flow3_persistence_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node DROP CONSTRAINT FK_820CADC84930C33C");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP CONSTRAINT FK_71DE9CFBA762B951");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node DROP CONSTRAINT FK_820CADC88D940019");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP CONSTRAINT FK_71DE9CFBE9BFE681");
        $this->addSql("DROP TABLE typo3_typo3cr_domain_model_contentobjectproxy");
        $this->addSql("DROP TABLE typo3_typo3cr_domain_model_node");
        $this->addSql("DROP TABLE typo3_typo3cr_domain_model_workspace");
    }
}
