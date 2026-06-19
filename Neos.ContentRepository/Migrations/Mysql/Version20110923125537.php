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

        foreach ($this->sm->listTableForeignKeys('typo3_typo3cr_domain_model_workspace') as $foreignKey) {
            if (in_array('typo3cr_workspace', array_map('strtolower', $foreignKey->getLocalColumns()), true)
                || in_array('typo3cr_node', array_map('strtolower', $foreignKey->getLocalColumns()), true)) {
                $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP FOREIGN KEY " . $foreignKey->getName());
            }
        }
        $indexes = $this->sm->listTableIndexes('typo3_typo3cr_domain_model_workspace');
        if (array_key_exists('idx_60e859ec60e859ec', $indexes)) {
            $this->addSql("DROP INDEX IDX_60E859EC60E859EC ON typo3_typo3cr_domain_model_workspace");
        }
        if (array_key_exists('idx_60e859ec45eb1a10', $indexes)) {
            $this->addSql("DROP INDEX IDX_60E859EC45EB1A10 ON typo3_typo3cr_domain_model_workspace");
        }
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace CHANGE typo3cr_workspace baseworkspace VARCHAR(40) DEFAULT NULL, CHANGE typo3cr_node rootnode VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT typo3_typo3cr_domain_model_workspace_ibfk_1 FOREIGN KEY (baseworkspace) REFERENCES typo3_typo3cr_domain_model_workspace(flow3_persistence_identifier)");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT typo3_typo3cr_domain_model_workspace_ibfk_2 FOREIGN KEY (rootnode) REFERENCES typo3_typo3cr_domain_model_node(flow3_persistence_identifier)");
        $this->addSql("CREATE INDEX IDX_71DE9CFBE9BFE681 ON typo3_typo3cr_domain_model_workspace (baseworkspace)");
        $this->addSql("CREATE INDEX IDX_71DE9CFB750166F ON typo3_typo3cr_domain_model_workspace (rootnode)");

        foreach ($this->sm->listTableForeignKeys('typo3_typo3cr_domain_model_node') as $foreignKey) {
            if (in_array('typo3cr_workspace', array_map('strtolower', $foreignKey->getLocalColumns()), true)
                || in_array('typo3cr_contentobjectproxy', array_map('strtolower', $foreignKey->getLocalColumns()), true)) {
                $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node DROP FOREIGN KEY " . $foreignKey->getName());
            }
        }
        $indexes = $this->sm->listTableIndexes('typo3_typo3cr_domain_model_node');
        if (array_key_exists('idx_45eb1a1060e859ec', $indexes)) {
            $this->addSql("DROP INDEX IDX_45EB1A1060E859EC ON typo3_typo3cr_domain_model_node");
        }
        if (array_key_exists('idx_45eb1a105e2a1f07', $indexes)) {
            $this->addSql("DROP INDEX IDX_45EB1A105E2A1F07 ON typo3_typo3cr_domain_model_node");
        }
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

        foreach ($this->sm->listTableForeignKeys('typo3_typo3cr_domain_model_node') as $foreignKey) {
            if (in_array('workspace', array_map('strtolower', $foreignKey->getLocalColumns()), true)
                || in_array('contentobjectproxy', array_map('strtolower', $foreignKey->getLocalColumns()), true)) {
                $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node DROP FOREIGN KEY " . $foreignKey->getName());
            }
        }
        $indexes = $this->sm->listTableIndexes('typo3_typo3cr_domain_model_node');
        if (array_key_exists('idx_820cadc88d940019', $indexes)) {
            $this->addSql("DROP INDEX IDX_820CADC88D940019 ON typo3_typo3cr_domain_model_node");
        }
        if (array_key_exists('idx_820cadc84930c33c', $indexes)) {
            $this->addSql("DROP INDEX IDX_820CADC84930C33C ON typo3_typo3cr_domain_model_node");
        }
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node CHANGE contentobjectproxy typo3cr_contentobjectproxy VARCHAR(40) DEFAULT NULL, CHANGE workspace typo3cr_workspace VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node ADD CONSTRAINT typo3_typo3cr_domain_model_node_ibfk_2 FOREIGN KEY (typo3cr_contentobjectproxy) REFERENCES typo3_typo3cr_domain_model_contentobjectproxy(flow3_persistence_identifier)");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node ADD CONSTRAINT typo3_typo3cr_domain_model_node_ibfk_1 FOREIGN KEY (typo3cr_workspace) REFERENCES typo3_typo3cr_domain_model_workspace(flow3_persistence_identifier) ON DELETE SET NULL");
        $this->addSql("CREATE INDEX IDX_45EB1A1060E859EC ON typo3_typo3cr_domain_model_node (typo3cr_workspace)");
        $this->addSql("CREATE INDEX IDX_45EB1A105E2A1F07 ON typo3_typo3cr_domain_model_node (typo3cr_contentobjectproxy)");

        foreach ($this->sm->listTableForeignKeys('typo3_typo3cr_domain_model_workspace') as $foreignKey) {
            if (in_array('baseworkspace', array_map('strtolower', $foreignKey->getLocalColumns()), true)
                || in_array('rootnode', array_map('strtolower', $foreignKey->getLocalColumns()), true)) {
                $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP FOREIGN KEY " . $foreignKey->getName());
            }
        }
        $indexes = $this->sm->listTableIndexes('typo3_typo3cr_domain_model_workspace');
        if (array_key_exists('idx_71de9cfbe9bfe681', $indexes)) {
            $this->addSql("DROP INDEX IDX_71DE9CFBE9BFE681 ON typo3_typo3cr_domain_model_workspace");
        }
        if (array_key_exists('idx_71de9cfb750166f', $indexes)) {
            $this->addSql("DROP INDEX IDX_71DE9CFB750166F ON typo3_typo3cr_domain_model_workspace");
        }
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace CHANGE rootnode typo3cr_node VARCHAR(40) DEFAULT NULL, CHANGE baseworkspace typo3cr_workspace VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT typo3_typo3cr_domain_model_workspace_ibfk_2 FOREIGN KEY (typo3cr_node) REFERENCES typo3_typo3cr_domain_model_node(flow3_persistence_identifier)");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT typo3_typo3cr_domain_model_workspace_ibfk_1 FOREIGN KEY (typo3cr_workspace) REFERENCES typo3_typo3cr_domain_model_workspace(flow3_persistence_identifier)");
        $this->addSql("CREATE INDEX IDX_60E859EC60E859EC ON typo3_typo3cr_domain_model_workspace (typo3cr_workspace)");
        $this->addSql("CREATE INDEX IDX_60E859EC45EB1A10 ON typo3_typo3cr_domain_model_workspace (typo3cr_node)");
    }
}
