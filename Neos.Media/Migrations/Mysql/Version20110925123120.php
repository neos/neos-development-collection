<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Create needed tables for Media package
 */
class Version20110925123120 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("CREATE TABLE typo3_media_domain_model_image (flow3_persistence_identifier VARCHAR(40) NOT NULL, resource VARCHAR(40) DEFAULT NULL, title VARCHAR(255) DEFAULT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, type INT DEFAULT NULL, imagevariants LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', INDEX IDX_7FA2358DBC91F416 (resource), PRIMARY KEY(flow3_persistence_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");

        $tableNames = $this->sm->listTableNames();
        if (array_search('typo3_flow3_resource_resource', $tableNames) !== false) {
            $this->addSql("ALTER TABLE typo3_media_domain_model_image ADD CONSTRAINT typo3_media_domain_model_image_ibfk_1 FOREIGN KEY (resource) REFERENCES typo3_flow3_resource_resource(flow3_persistence_identifier)");
        } elseif (array_search('typo3_flow_resource_resource', $tableNames) !== false) {
            $this->addSql("ALTER TABLE typo3_media_domain_model_image ADD CONSTRAINT typo3_media_domain_model_image_ibfk_1 FOREIGN KEY (resource) REFERENCES typo3_flow_resource_resource(persistence_object_identifier)");
        }
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("DROP TABLE typo3_media_domain_model_image");
    }
}
