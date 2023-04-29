<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust table names to the renaming of TYPO3.Media to Neos.Media.
 */
class Version20161125093811 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('ALTER TABLE typo3_media_domain_model_adjustment_abstractimageadjustment RENAME TO neos_media_domain_model_adjustment_abstractimageadjustment');
        $this->addSql('ALTER TABLE typo3_media_domain_model_asset RENAME TO neos_media_domain_model_asset');
        $this->addSql('ALTER TABLE typo3_media_domain_model_asset_tags_join RENAME TO neos_media_domain_model_asset_tags_join');
        $this->addSql('ALTER TABLE typo3_media_domain_model_assetcollection RENAME TO neos_media_domain_model_assetcollection');
        $this->addSql('ALTER TABLE typo3_media_domain_model_assetcollection_assets_join RENAME TO neos_media_domain_model_assetcollection_assets_join');
        $this->addSql('ALTER TABLE typo3_media_domain_model_assetcollection_tags_join RENAME TO neos_media_domain_model_assetcollection_tags_join');
        $this->addSql('ALTER TABLE typo3_media_domain_model_audio RENAME TO neos_media_domain_model_audio');
        $this->addSql('ALTER TABLE typo3_media_domain_model_document RENAME TO neos_media_domain_model_document');
        $this->addSql('ALTER TABLE typo3_media_domain_model_image RENAME TO neos_media_domain_model_image');
        $this->addSql('ALTER TABLE typo3_media_domain_model_imagevariant RENAME TO neos_media_domain_model_imagevariant');
        $this->addSql('ALTER TABLE typo3_media_domain_model_tag RENAME TO neos_media_domain_model_tag');
        $this->addSql('ALTER TABLE typo3_media_domain_model_thumbnail RENAME TO neos_media_domain_model_thumbnail');
        $this->addSql('ALTER TABLE typo3_media_domain_model_video RENAME TO neos_media_domain_model_video');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('ALTER TABLE neos_media_domain_model_adjustment_abstractimageadjustment RENAME TO typo3_media_domain_model_adjustment_abstractimageadjustment');
        $this->addSql('ALTER TABLE neos_media_domain_model_asset RENAME TO typo3_media_domain_model_asset');
        $this->addSql('ALTER TABLE neos_media_domain_model_asset_tags_join RENAME TO typo3_media_domain_model_asset_tags_join');
        $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection RENAME TO typo3_media_domain_model_assetcollection');
        $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_assets_join RENAME TO typo3_media_domain_model_assetcollection_assets_join');
        $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_tags_join RENAME TO typo3_media_domain_model_assetcollection_tags_join');
        $this->addSql('ALTER TABLE neos_media_domain_model_audio RENAME TO typo3_media_domain_model_audio');
        $this->addSql('ALTER TABLE neos_media_domain_model_document RENAME TO typo3_media_domain_model_document');
        $this->addSql('ALTER TABLE neos_media_domain_model_image RENAME TO typo3_media_domain_model_image');
        $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant RENAME TO typo3_media_domain_model_imagevariant');
        $this->addSql('ALTER TABLE neos_media_domain_model_tag RENAME TO typo3_media_domain_model_tag');
        $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail RENAME TO typo3_media_domain_model_thumbnail');
        $this->addSql('ALTER TABLE neos_media_domain_model_video RENAME TO typo3_media_domain_model_video');
    }
}
