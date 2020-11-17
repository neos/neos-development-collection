<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust default values to NOT NULL unless allowed in model.
 */
class Version20120329220342 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_contentobjectproxy CHANGE targettype targettype VARCHAR(255) NOT NULL, CHANGE targetid targetid VARCHAR(255) NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node CHANGE path path VARCHAR(255) NOT NULL, CHANGE identifier identifier VARCHAR(255) NOT NULL, CHANGE properties properties LONGTEXT NOT NULL COMMENT '(DC2Type:array)', CHANGE contenttype contenttype VARCHAR(255) NOT NULL, CHANGE removed removed TINYINT(1) NOT NULL, CHANGE hidden hidden TINYINT(1) NOT NULL, CHANGE hiddeninindex hiddeninindex TINYINT(1) NOT NULL, CHANGE accessroles accessroles LONGTEXT NOT NULL COMMENT '(DC2Type:array)', CHANGE version version INT NOT NULL, CHANGE parentpath parentpath VARCHAR(255) NOT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace CHANGE name name VARCHAR(255) NOT NULL");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_contentobjectproxy CHANGE targettype targettype VARCHAR(255) DEFAULT NULL, CHANGE targetid targetid VARCHAR(255) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_node CHANGE version version INT DEFAULT 1, CHANGE path path VARCHAR(255) DEFAULT NULL, CHANGE parentpath parentpath VARCHAR(255) DEFAULT NULL, CHANGE identifier identifier VARCHAR(255) DEFAULT NULL, CHANGE properties properties LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', CHANGE contenttype contenttype VARCHAR(255) DEFAULT NULL, CHANGE removed removed TINYINT(1) DEFAULT NULL, CHANGE hidden hidden TINYINT(1) DEFAULT NULL, CHANGE hiddeninindex hiddeninindex TINYINT(1) DEFAULT NULL, CHANGE accessroles accessroles LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)'");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_workspace CHANGE name name VARCHAR(255) DEFAULT NULL");
    }
}
