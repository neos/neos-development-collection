<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Database structure as per the T3CON11 CfP launch on 2011-05-20
 */
class Version20110620155001 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("CREATE TABLE typo3cr_contentobjectproxy (flow3_persistence_identifier VARCHAR(40) NOT NULL, targettype VARCHAR(255) DEFAULT NULL, targetid VARCHAR(255) DEFAULT NULL, PRIMARY KEY(flow3_persistence_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE typo3cr_contenttype (flow3_persistence_identifier VARCHAR(40) NOT NULL, name VARCHAR(255) DEFAULT NULL, PRIMARY KEY(flow3_persistence_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE contentTypesDeclaredSuperTypes (typo3cr_contenttype VARCHAR(40) NOT NULL, declaredSuperTypeId VARCHAR(40) NOT NULL, INDEX IDX_BEE1B2BEE2741BEE (declaredSuperTypeId), INDEX IDX_BEE1B2BEF2209F2 (typo3cr_contenttype), PRIMARY KEY(declaredSuperTypeId, typo3cr_contenttype)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE typo3cr_node (flow3_persistence_identifier VARCHAR(40) NOT NULL, typo3cr_workspace VARCHAR(40) DEFAULT NULL, typo3cr_contentobjectproxy VARCHAR(40) DEFAULT NULL, path VARCHAR(255) DEFAULT NULL, identifier VARCHAR(255) DEFAULT NULL, depth INT DEFAULT NULL, sorting_index VARCHAR(255) DEFAULT NULL, properties LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', contenttype VARCHAR(255) DEFAULT NULL, removed TINYINT(1) DEFAULT NULL, hidden TINYINT(1) DEFAULT NULL, hiddenbeforedate DATETIME DEFAULT NULL, hiddenafterdate DATETIME DEFAULT NULL, hiddeninindex TINYINT(1) DEFAULT NULL, accessroles LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', INDEX IDX_45EB1A1060E859EC (typo3cr_workspace), INDEX IDX_45EB1A105E2A1F07 (typo3cr_contentobjectproxy), PRIMARY KEY(flow3_persistence_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE typo3cr_workspace (flow3_persistence_identifier VARCHAR(40) NOT NULL, typo3cr_workspace VARCHAR(40) DEFAULT NULL, typo3cr_node VARCHAR(40) DEFAULT NULL, name VARCHAR(255) DEFAULT NULL, INDEX IDX_60E859EC60E859EC (typo3cr_workspace), INDEX IDX_60E859EC45EB1A10 (typo3cr_node), PRIMARY KEY(flow3_persistence_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE contentTypesDeclaredSuperTypes ADD CONSTRAINT contentTypesDeclaredSuperTypes_ibfk_1 FOREIGN KEY (declaredSuperTypeId) REFERENCES typo3cr_contenttype(flow3_persistence_identifier)");
        $this->addSql("ALTER TABLE contentTypesDeclaredSuperTypes ADD CONSTRAINT contentTypesDeclaredSuperTypes_ibfk_2 FOREIGN KEY (typo3cr_contenttype) REFERENCES typo3cr_contenttype(flow3_persistence_identifier)");
        $this->addSql("ALTER TABLE typo3cr_node ADD CONSTRAINT typo3cr_node_ibfk_1 FOREIGN KEY (typo3cr_workspace) REFERENCES typo3cr_workspace(flow3_persistence_identifier) ON DELETE SET NULL");
        $this->addSql("ALTER TABLE typo3cr_node ADD CONSTRAINT typo3cr_node_ibfk_2 FOREIGN KEY (typo3cr_contentobjectproxy) REFERENCES typo3cr_contentobjectproxy(flow3_persistence_identifier)");
        $this->addSql("ALTER TABLE typo3cr_workspace ADD CONSTRAINT typo3cr_workspace_ibfk_1 FOREIGN KEY (typo3cr_workspace) REFERENCES typo3cr_workspace(flow3_persistence_identifier)");
        $this->addSql("ALTER TABLE typo3cr_workspace ADD CONSTRAINT typo3cr_workspace_ibfk_2 FOREIGN KEY (typo3cr_node) REFERENCES typo3cr_node(flow3_persistence_identifier)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3cr_node DROP FOREIGN KEY typo3cr_node_ibfk_2");
        $this->addSql("ALTER TABLE contentTypesDeclaredSuperTypes DROP FOREIGN KEY contenttypesdeclaredsupertypes_ibfk_2");
        $this->addSql("ALTER TABLE contentTypesDeclaredSuperTypes DROP FOREIGN KEY contenttypesdeclaredsupertypes_ibfk_1");
        $this->addSql("ALTER TABLE typo3cr_workspace DROP FOREIGN KEY typo3cr_workspace_ibfk_2");
        $this->addSql("ALTER TABLE typo3cr_node DROP FOREIGN KEY typo3cr_node_ibfk_1");
        $this->addSql("ALTER TABLE typo3cr_workspace DROP FOREIGN KEY typo3cr_workspace_ibfk_1");
        $this->addSql("DROP TABLE typo3cr_contentobjectproxy");
        $this->addSql("DROP TABLE typo3cr_contenttype");
        $this->addSql("DROP TABLE contentTypesDeclaredSuperTypes");
        $this->addSql("DROP TABLE typo3cr_node");
        $this->addSql("DROP TABLE typo3cr_workspace");
    }
}
