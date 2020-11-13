<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Migration that adds position to ImageAdjustments.
 */
class Version20150228154201 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        $this->addSql("ALTER TABLE typo3_media_domain_model_adjustment_abstractimageadjustment ADD position INT NOT NULL");
        $this->addSql("UPDATE typo3_media_domain_model_adjustment_abstractimageadjustment SET position = 10 WHERE dtype = 'typo3_media_adjustment_cropimageadjustment'");
        $this->addSql("UPDATE typo3_media_domain_model_adjustment_abstractimageadjustment SET position = 20 WHERE dtype = 'typo3_media_adjustment_resizeimageadjustment'");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_adjustment_abstractimageadjustment DROP position");
    }
}
