<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Creates the table for the TYPO3 Media\Image model
 */
class Version20110919164835 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("CREATE TABLE typo3_typo3_domain_model_media_image (flow3_persistence_identifier VARCHAR(40) NOT NULL, flow3_resource_resource VARCHAR(40) DEFAULT NULL, UNIQUE INDEX UNIQ_E5EA82E211FFD19F (flow3_resource_resource), PRIMARY KEY(flow3_persistence_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_media_image ADD CONSTRAINT typo3_typo3_domain_model_media_image_ibfk_1 FOREIGN KEY (flow3_resource_resource) REFERENCES typo3_flow3_resource_resource(flow3_persistence_identifier)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("DROP TABLE typo3_typo3_domain_model_media_image");
    }
}
