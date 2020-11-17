<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Note: The migrations date predates the actual change because for Neos it needs to be executed before some other migrations to make everything work correctly.
 *
 * Add property allowupscaling in typo3_media_domain_model_adjustment_abstractimageadjustment
 */
class Version20150324185018 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        $this->addSql("ALTER TABLE typo3_media_domain_model_adjustment_abstractimageadjustment ADD allowupscaling TINYINT(1) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail ADD allowupscaling TINYINT(1) DEFAULT NULL");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        $this->addSql("ALTER TABLE typo3_media_domain_model_adjustment_abstractimageadjustment DROP allowupscaling");
        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail DROP allowupscaling");
    }
}
