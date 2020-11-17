<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust table names to the renaming of TYPO3.Media to Neos.Media.
 */
class Version20161125093810 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('RENAME TABLE typo3_media_domain_model_adjustment_abstractimageadjustment TO neos_media_domain_model_adjustment_abstractimageadjustment');
        $this->addSql('RENAME TABLE typo3_media_domain_model_asset TO neos_media_domain_model_asset');
        $this->addSql('RENAME TABLE typo3_media_domain_model_asset_tags_join TO neos_media_domain_model_asset_tags_join');
        $this->addSql('RENAME TABLE typo3_media_domain_model_assetcollection TO neos_media_domain_model_assetcollection');
        $this->addSql('RENAME TABLE typo3_media_domain_model_assetcollection_assets_join TO neos_media_domain_model_assetcollection_assets_join');
        $this->addSql('RENAME TABLE typo3_media_domain_model_assetcollection_tags_join TO neos_media_domain_model_assetcollection_tags_join');
        $this->addSql('RENAME TABLE typo3_media_domain_model_audio TO neos_media_domain_model_audio');
        $this->addSql('RENAME TABLE typo3_media_domain_model_document TO neos_media_domain_model_document');
        $this->addSql('RENAME TABLE typo3_media_domain_model_image TO neos_media_domain_model_image');
        $this->addSql('RENAME TABLE typo3_media_domain_model_imagevariant TO neos_media_domain_model_imagevariant');
        $this->addSql('RENAME TABLE typo3_media_domain_model_tag TO neos_media_domain_model_tag');
        $this->addSql('RENAME TABLE typo3_media_domain_model_thumbnail TO neos_media_domain_model_thumbnail');
        $this->addSql('RENAME TABLE typo3_media_domain_model_video TO neos_media_domain_model_video');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('RENAME TABLE neos_media_domain_model_adjustment_abstractimageadjustment TO typo3_media_domain_model_adjustment_abstractimageadjustment');
        $this->addSql('RENAME TABLE neos_media_domain_model_asset TO typo3_media_domain_model_asset');
        $this->addSql('RENAME TABLE neos_media_domain_model_asset_tags_join TO typo3_media_domain_model_asset_tags_join');
        $this->addSql('RENAME TABLE neos_media_domain_model_assetcollection TO typo3_media_domain_model_assetcollection');
        $this->addSql('RENAME TABLE neos_media_domain_model_assetcollection_assets_join TO typo3_media_domain_model_assetcollection_assets_join');
        $this->addSql('RENAME TABLE neos_media_domain_model_assetcollection_tags_join TO typo3_media_domain_model_assetcollection_tags_join');
        $this->addSql('RENAME TABLE neos_media_domain_model_audio TO typo3_media_domain_model_audio');
        $this->addSql('RENAME TABLE neos_media_domain_model_document TO typo3_media_domain_model_document');
        $this->addSql('RENAME TABLE neos_media_domain_model_image TO typo3_media_domain_model_image');
        $this->addSql('RENAME TABLE neos_media_domain_model_imagevariant TO typo3_media_domain_model_imagevariant');
        $this->addSql('RENAME TABLE neos_media_domain_model_tag TO typo3_media_domain_model_tag');
        $this->addSql('RENAME TABLE neos_media_domain_model_thumbnail TO typo3_media_domain_model_thumbnail');
        $this->addSql('RENAME TABLE neos_media_domain_model_video TO typo3_media_domain_model_video');
    }
}
