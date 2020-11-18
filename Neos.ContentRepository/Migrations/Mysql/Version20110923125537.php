<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Fix column names for direct associations
 */
class Version20110923125537 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP FOREIGN KEY typo3_typo3cr_domain_model_workspace_ibfk_2");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP FOREIGN KEY typo3_typo3cr_domain_model_workspace_ibfk_1");
        $this->addSql("DROP INDEX IDX_60E859EC60E859EC ON typo3_typo3cr_domain_model_workspace");
        $this->addSql("DROP INDEX IDX_60E859EC45EB1A10 ON typo3_typo3cr_domain_model_workspace");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace CHANGE typo3cr_workspace baseworkspace VARCHAR(40) DEFAULT NULL, CHANGE typo3cr_node rootnode VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT typo3_typo3cr_domain_model_workspace_ibfk_1 FOREIGN KEY (baseworkspace) REFERENCES typo3_typo3cr_domain_model_workspace(flow3_persistence_identifier)");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT typo3_typo3cr_domain_model_workspace_ibfk_2 FOREIGN KEY (rootnode) REFERENCES typo3_typo3cr_domain_model_node(flow3_persistence_identifier)");
        $this->addSql("CREATE INDEX IDX_71DE9CFBE9BFE681 ON typo3_typo3cr_domain_model_workspace (baseworkspace)");
        $this->addSql("CREATE INDEX IDX_71DE9CFB750166F ON typo3_typo3cr_domain_model_workspace (rootnode)");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node DROP FOREIGN KEY typo3_typo3cr_domain_model_node_ibfk_2");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node DROP FOREIGN KEY typo3_typo3cr_domain_model_node_ibfk_1");
        $this->addSql("DROP INDEX IDX_45EB1A1060E859EC ON typo3_typo3cr_domain_model_node");
        $this->addSql("DROP INDEX IDX_45EB1A105E2A1F07 ON typo3_typo3cr_domain_model_node");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node CHANGE typo3cr_workspace workspace VARCHAR(40) DEFAULT NULL, CHANGE typo3cr_contentobjectproxy contentobjectproxy VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node ADD CONSTRAINT typo3_typo3cr_domain_model_node_ibfk_1 FOREIGN KEY (workspace) REFERENCES typo3_typo3cr_domain_model_workspace(flow3_persistence_identifier) ON DELETE SET NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node ADD CONSTRAINT typo3_typo3cr_domain_model_node_ibfk_2 FOREIGN KEY (contentobjectproxy) REFERENCES typo3_typo3cr_domain_model_contentobjectproxy(flow3_persistence_identifier)");
        $this->addSql("CREATE INDEX IDX_820CADC88D940019 ON typo3_typo3cr_domain_model_node (workspace)");
        $this->addSql("CREATE INDEX IDX_820CADC84930C33C ON typo3_typo3cr_domain_model_node (contentobjectproxy)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node DROP FOREIGN KEY typo3_typo3cr_domain_model_node_ibfk_2");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node DROP FOREIGN KEY typo3_typo3cr_domain_model_node_ibfk_1");
        $this->addSql("DROP INDEX IDX_820CADC88D940019 ON typo3_typo3cr_domain_model_node");
        $this->addSql("DROP INDEX IDX_820CADC84930C33C ON typo3_typo3cr_domain_model_node");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node CHANGE contentobjectproxy typo3cr_contentobjectproxy VARCHAR(40) DEFAULT NULL, CHANGE workspace typo3cr_workspace VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node ADD CONSTRAINT typo3_typo3cr_domain_model_node_ibfk_2 FOREIGN KEY (typo3cr_contentobjectproxy) REFERENCES typo3_typo3cr_domain_model_contentobjectproxy(flow3_persistence_identifier)");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node ADD CONSTRAINT typo3_typo3cr_domain_model_node_ibfk_1 FOREIGN KEY (typo3cr_workspace) REFERENCES typo3_typo3cr_domain_model_workspace(flow3_persistence_identifier) ON DELETE SET NULL");
        $this->addSql("CREATE INDEX IDX_45EB1A1060E859EC ON typo3_typo3cr_domain_model_node (typo3cr_workspace)");
        $this->addSql("CREATE INDEX IDX_45EB1A105E2A1F07 ON typo3_typo3cr_domain_model_node (typo3cr_contentobjectproxy)");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP FOREIGN KEY typo3_typo3cr_domain_model_workspace_ibfk_2");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP FOREIGN KEY typo3_typo3cr_domain_model_workspace_ibfk_1");
        $this->addSql("DROP INDEX IDX_71DE9CFBE9BFE681 ON typo3_typo3cr_domain_model_workspace");
        $this->addSql("DROP INDEX IDX_71DE9CFB750166F ON typo3_typo3cr_domain_model_workspace");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace CHANGE rootnode typo3cr_node VARCHAR(40) DEFAULT NULL, CHANGE baseworkspace typo3cr_workspace VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT typo3_typo3cr_domain_model_workspace_ibfk_2 FOREIGN KEY (typo3cr_node) REFERENCES typo3_typo3cr_domain_model_node(flow3_persistence_identifier)");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT typo3_typo3cr_domain_model_workspace_ibfk_1 FOREIGN KEY (typo3cr_workspace) REFERENCES typo3_typo3cr_domain_model_workspace(flow3_persistence_identifier)");
        $this->addSql("CREATE INDEX IDX_60E859EC60E859EC ON typo3_typo3cr_domain_model_workspace (typo3cr_workspace)");
        $this->addSql("CREATE INDEX IDX_60E859EC45EB1A10 ON typo3_typo3cr_domain_model_workspace (typo3cr_node)");
    }
}
