<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Allow NULL values for image dimensions
 */
class Version20150701113247 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_image ALTER width DROP NOT NULL");
        $this->addSql("ALTER TABLE typo3_media_domain_model_image ALTER height DROP NOT NULL");
        $this->addSql("ALTER TABLE typo3_media_domain_model_imagevariant ALTER width DROP NOT NULL");
        $this->addSql("ALTER TABLE typo3_media_domain_model_imagevariant ALTER height DROP NOT NULL");
        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail ALTER width DROP NOT NULL");
        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail ALTER height DROP NOT NULL");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_image ALTER width SET NOT NULL");
        $this->addSql("ALTER TABLE typo3_media_domain_model_image ALTER height SET NOT NULL");
        $this->addSql("ALTER TABLE typo3_media_domain_model_imagevariant ALTER width SET NOT NULL");
        $this->addSql("ALTER TABLE typo3_media_domain_model_imagevariant ALTER height SET NOT NULL");
        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail ALTER width SET NOT NULL");
        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail ALTER height SET NOT NULL");
    }
}
