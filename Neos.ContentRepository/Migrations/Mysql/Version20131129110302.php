<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Migration to remove persistence_object_identifier from workspace model. The workspace name is used as identifier.
 */
class Version20131129110302 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP FOREIGN KEY typo3_typo3cr_domain_model_nodedata_ibfk_1");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP FOREIGN KEY FK_71DE9CFBE9BFE681");
        $this->addSql("DROP INDEX flow3_identity_typo3_typo3cr_domain_model_workspace ON typo3_typo3cr_domain_model_workspace");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP PRIMARY KEY, ADD PRIMARY KEY (name)");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata CHANGE workspace workspace VARCHAR(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace CHANGE baseworkspace baseworkspace VARCHAR(255) DEFAULT NULL");

        $this->addSql("UPDATE typo3_typo3cr_domain_model_workspace workspace INNER JOIN typo3_typo3cr_domain_model_workspace base ON base.persistence_object_identifier = workspace.baseworkspace SET workspace.baseworkspace = base.name");
        $this->addSql("UPDATE typo3_typo3cr_domain_model_nodedata nodedata INNER JOIN typo3_typo3cr_domain_model_workspace workspace ON workspace.persistence_object_identifier = nodedata.workspace SET nodedata.workspace = workspace.name");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP persistence_object_identifier");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT FK_71DE9CFBE9BFE681 FOREIGN KEY (baseworkspace) REFERENCES typo3_typo3cr_domain_model_workspace (name) ON DELETE SET NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD CONSTRAINT FK_60A956B98D940019 FOREIGN KEY (workspace) REFERENCES typo3_typo3cr_domain_model_workspace (name) ON DELETE SET NULL");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP FOREIGN KEY FK_71DE9CFBE9BFE681");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP FOREIGN KEY FK_60A956B98D940019");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD persistence_object_identifier VARCHAR(40) NOT NULL");

        $this->addSql("UPDATE typo3_typo3cr_domain_model_workspace SET persistence_object_identifier = UUID()");
        $this->addSql("UPDATE typo3_typo3cr_domain_model_workspace workspace INNER JOIN typo3_typo3cr_domain_model_workspace base ON base.name = workspace.baseworkspace SET workspace.baseworkspace = base.persistence_object_identifier");
        $this->addSql("UPDATE typo3_typo3cr_domain_model_nodedata nodedata INNER JOIN typo3_typo3cr_domain_model_workspace workspace ON workspace.name = nodedata.workspace SET nodedata.workspace = workspace.persistence_object_identifier");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace CHANGE baseworkspace baseworkspace VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata CHANGE workspace workspace VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace DROP PRIMARY KEY, ADD PRIMARY KEY (persistence_object_identifier)");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace ADD CONSTRAINT FK_71DE9CFBE9BFE681 FOREIGN KEY (baseworkspace) REFERENCES typo3_typo3cr_domain_model_workspace (persistence_object_identifier) ON DELETE SET NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD CONSTRAINT typo3_typo3cr_domain_model_nodedata_ibfk_1 FOREIGN KEY (workspace) REFERENCES typo3_typo3cr_domain_model_workspace (persistence_object_identifier) ON DELETE SET NULL");
        $this->addSql("CREATE UNIQUE INDEX flow3_identity_typo3_typo3cr_domain_model_workspace ON typo3_typo3cr_domain_model_workspace (name)");
    }
}
