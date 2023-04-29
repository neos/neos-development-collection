<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Migration that adds position to ImageAdjustments.
 */
class Version20150305113900 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_adjustment_abstractimageadjustment ADD position INT NULL");
        $this->addSql("UPDATE typo3_media_domain_model_adjustment_abstractimageadjustment SET position = 10 WHERE dtype = 'typo3_media_adjustment_cropimageadjustment'");
        $this->addSql("UPDATE typo3_media_domain_model_adjustment_abstractimageadjustment SET position = 20 WHERE dtype = 'typo3_media_adjustment_resizeimageadjustment'");
        $this->addSql("ALTER TABLE typo3_media_domain_model_adjustment_abstractimageadjustment ALTER position SET NOT NULL");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_adjustment_abstractimageadjustment DROP position");
    }
}
