<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Allow NULL values for image dimensions
 */
class Version20150701113246 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_image CHANGE width width INT DEFAULT NULL, CHANGE height height INT DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_media_domain_model_imagevariant CHANGE width width INT DEFAULT NULL, CHANGE height height INT DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail CHANGE width width INT DEFAULT NULL, CHANGE height height INT DEFAULT NULL");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_image CHANGE width width INT NOT NULL, CHANGE height height INT NOT NULL");
        $this->addSql("ALTER TABLE typo3_media_domain_model_imagevariant CHANGE width width INT NOT NULL, CHANGE height height INT NOT NULL");
        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail CHANGE width width INT NOT NULL, CHANGE height height INT NOT NULL");
    }
}
