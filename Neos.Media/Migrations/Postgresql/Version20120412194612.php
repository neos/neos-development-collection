<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Create tables for PostgreSQL
 */
class Version20120412194612 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("CREATE TABLE typo3_media_domain_model_image (flow3_persistence_identifier VARCHAR(40) NOT NULL, resource VARCHAR(40) DEFAULT NULL, title VARCHAR(255) NOT NULL, width INT NOT NULL, height INT NOT NULL, type INT NOT NULL, imagevariants TEXT NOT NULL, PRIMARY KEY(flow3_persistence_identifier))");
        $this->addSql("CREATE INDEX IDX_7FA2358DBC91F416 ON typo3_media_domain_model_image (resource)");
        $this->addSql("COMMENT ON COLUMN typo3_media_domain_model_image.imagevariants IS '(DC2Type:array)'");

        $tableNames = $this->sm->listTableNames();
        if (array_search('typo3_flow3_resource_resource', $tableNames) !== false) {
            $this->addSql("ALTER TABLE typo3_media_domain_model_image ADD CONSTRAINT FK_7FA2358DBC91F416 FOREIGN KEY (resource) REFERENCES typo3_flow3_resource_resource (flow3_persistence_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
        } elseif (array_search('typo3_flow_resource_resource', $tableNames) !== false) {
            $this->addSql("ALTER TABLE typo3_media_domain_model_image ADD CONSTRAINT FK_7FA2358DBC91F416 FOREIGN KEY (resource) REFERENCES typo3_flow_resource_resource (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
        }
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_image DROP CONSTRAINT FK_7FA2358DBC91F416");
        $this->addSql("DROP TABLE typo3_media_domain_model_image");
    }
}
