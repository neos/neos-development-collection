<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Platforms\MySQL57Platform;
use Doctrine\DBAL\Schema\Schema;

class Version20170110130217 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription(): string 
    {
        return 'Adjust foreign key and index names to the renaming of TYPO3.Media to Neos.Media';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        // Renaming of indexes is only possible with MySQL version 5.7+
        if ($this->connection->getDatabasePlatform() instanceof MySQL57Platform) {
            $this->addSql('ALTER TABLE neos_media_domain_model_adjustment_abstractimageadjustment RENAME INDEX idx_84416fdca76d06e6 TO IDX_8B2F26F8A76D06E6');
            $this->addSql('ALTER TABLE neos_media_domain_model_asset RENAME INDEX uniq_b8306b8ebc91f416 TO UNIQ_675F9550BC91F416');
            $this->addSql('ALTER TABLE neos_media_domain_model_asset_tags_join RENAME INDEX idx_daf7a1eb1db69eed TO IDX_915BC7A21DB69EED');
            $this->addSql('ALTER TABLE neos_media_domain_model_asset_tags_join RENAME INDEX idx_daf7a1eb48d8c57e TO IDX_915BC7A248D8C57E');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_assets_join RENAME INDEX idx_e90d72512a965871 TO IDX_1305D4CE2A965871');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_assets_join RENAME INDEX idx_e90d72511db69eed TO IDX_1305D4CE1DB69EED');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_tags_join RENAME INDEX idx_a41705672a965871 TO IDX_522F02632A965871');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_tags_join RENAME INDEX idx_a417056748d8c57e TO IDX_522F026348D8C57E');
            $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant RENAME INDEX idx_758edebd55ff4171 TO IDX_C4BF979F55FF4171');
            $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail RENAME INDEX idx_b7ce141455ff4171 TO IDX_3A163C4955FF4171');
            $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail RENAME INDEX uniq_b7ce1414bc91f416 TO UNIQ_3A163C49BC91F416');
            $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail RENAME INDEX originalasset_configurationhash TO UNIQ_3A163C4955FF41717F7CBA1A');
        } else {
            $this->addSql('ALTER TABLE neos_media_domain_model_adjustment_abstractimageadjustment DROP FOREIGN KEY FK_84416FDCA76D06E6');
            $this->addSql('DROP INDEX idx_84416fdca76d06e6 ON neos_media_domain_model_adjustment_abstractimageadjustment');
            $this->addSql('CREATE INDEX IDX_8B2F26F8A76D06E6 ON neos_media_domain_model_adjustment_abstractimageadjustment (imagevariant)');
            $this->addSql('ALTER TABLE neos_media_domain_model_adjustment_abstractimageadjustment ADD CONSTRAINT FK_84416FDCA76D06E6 FOREIGN KEY (imagevariant) REFERENCES neos_media_domain_model_imagevariant (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_media_domain_model_asset DROP FOREIGN KEY FK_B8306B8EBC91F416');
            $this->addSql('DROP INDEX uniq_b8306b8ebc91f416 ON neos_media_domain_model_asset');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_675F9550BC91F416 ON neos_media_domain_model_asset (resource)');
            $this->addSql('ALTER TABLE neos_media_domain_model_asset ADD CONSTRAINT FK_B8306B8EBC91F416 FOREIGN KEY (resource) REFERENCES neos_flow_resourcemanagement_persistentresource (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_media_domain_model_asset_tags_join DROP FOREIGN KEY FK_DAF7A1EB1DB69EED');
            $this->addSql('ALTER TABLE neos_media_domain_model_asset_tags_join DROP FOREIGN KEY FK_DAF7A1EB48D8C57E');
            $this->addSql('DROP INDEX idx_daf7a1eb1db69eed ON neos_media_domain_model_asset_tags_join');
            $this->addSql('CREATE INDEX IDX_915BC7A21DB69EED ON neos_media_domain_model_asset_tags_join (media_asset)');
            $this->addSql('DROP INDEX idx_daf7a1eb48d8c57e ON neos_media_domain_model_asset_tags_join');
            $this->addSql('CREATE INDEX IDX_915BC7A248D8C57E ON neos_media_domain_model_asset_tags_join (media_tag)');
            $this->addSql('ALTER TABLE neos_media_domain_model_asset_tags_join ADD CONSTRAINT FK_DAF7A1EB1DB69EED FOREIGN KEY (media_asset) REFERENCES neos_media_domain_model_asset (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_media_domain_model_asset_tags_join ADD CONSTRAINT FK_DAF7A1EB48D8C57E FOREIGN KEY (media_tag) REFERENCES neos_media_domain_model_tag (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_assets_join DROP FOREIGN KEY FK_E90D72511DB69EED');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_assets_join DROP FOREIGN KEY FK_E90D72512A965871');
            $this->addSql('DROP INDEX idx_e90d72512a965871 ON neos_media_domain_model_assetcollection_assets_join');
            $this->addSql('CREATE INDEX IDX_1305D4CE2A965871 ON neos_media_domain_model_assetcollection_assets_join (media_assetcollection)');
            $this->addSql('DROP INDEX idx_e90d72511db69eed ON neos_media_domain_model_assetcollection_assets_join');
            $this->addSql('CREATE INDEX IDX_1305D4CE1DB69EED ON neos_media_domain_model_assetcollection_assets_join (media_asset)');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_assets_join ADD CONSTRAINT FK_E90D72511DB69EED FOREIGN KEY (media_asset) REFERENCES neos_media_domain_model_asset (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_assets_join ADD CONSTRAINT FK_E90D72512A965871 FOREIGN KEY (media_assetcollection) REFERENCES neos_media_domain_model_assetcollection (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_tags_join DROP FOREIGN KEY FK_A41705672A965871');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_tags_join DROP FOREIGN KEY FK_A417056748D8C57E');
            $this->addSql('DROP INDEX idx_a41705672a965871 ON neos_media_domain_model_assetcollection_tags_join');
            $this->addSql('CREATE INDEX IDX_522F02632A965871 ON neos_media_domain_model_assetcollection_tags_join (media_assetcollection)');
            $this->addSql('DROP INDEX idx_a417056748d8c57e ON neos_media_domain_model_assetcollection_tags_join');
            $this->addSql('CREATE INDEX IDX_522F026348D8C57E ON neos_media_domain_model_assetcollection_tags_join (media_tag)');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_tags_join ADD CONSTRAINT FK_A41705672A965871 FOREIGN KEY (media_assetcollection) REFERENCES neos_media_domain_model_assetcollection (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_tags_join ADD CONSTRAINT FK_A417056748D8C57E FOREIGN KEY (media_tag) REFERENCES neos_media_domain_model_tag (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant DROP FOREIGN KEY FK_758EDEBD55FF4171');
            $this->addSql('DROP INDEX idx_758edebd55ff4171 ON neos_media_domain_model_imagevariant');
            $this->addSql('CREATE INDEX IDX_C4BF979F55FF4171 ON neos_media_domain_model_imagevariant (originalasset)');
            $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant ADD CONSTRAINT FK_758EDEBD55FF4171 FOREIGN KEY (originalasset) REFERENCES neos_media_domain_model_image (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail DROP FOREIGN KEY FK_B7CE141455FF4171');
            $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail DROP FOREIGN KEY FK_B7CE1414BC91F416');
            $this->addSql('DROP INDEX idx_b7ce141455ff4171 ON neos_media_domain_model_thumbnail');
            $this->addSql('CREATE INDEX IDX_3A163C4955FF4171 ON neos_media_domain_model_thumbnail (originalasset)');
            $this->addSql('DROP INDEX uniq_b7ce1414bc91f416 ON neos_media_domain_model_thumbnail');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_3A163C49BC91F416 ON neos_media_domain_model_thumbnail (resource)');
            $this->addSql('DROP INDEX originalasset_configurationhash ON neos_media_domain_model_thumbnail');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_3A163C4955FF41717F7CBA1A ON neos_media_domain_model_thumbnail (originalasset, configurationhash)');
            $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail ADD CONSTRAINT FK_B7CE141455FF4171 FOREIGN KEY (originalasset) REFERENCES neos_media_domain_model_asset (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail ADD CONSTRAINT FK_B7CE1414BC91F416 FOREIGN KEY (resource) REFERENCES neos_flow_resourcemanagement_persistentresource (persistence_object_identifier)');
        }
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        // Renaming of indexes is only possible with MySQL version 5.7+
        if ($this->connection->getDatabasePlatform() instanceof MySQL57Platform) {
            $this->addSql('ALTER TABLE neos_media_domain_model_adjustment_abstractimageadjustment RENAME INDEX idx_8b2f26f8a76d06e6 TO IDX_84416FDCA76D06E6');
            $this->addSql('ALTER TABLE neos_media_domain_model_asset RENAME INDEX uniq_675f9550bc91f416 TO UNIQ_B8306B8EBC91F416');
            $this->addSql('ALTER TABLE neos_media_domain_model_asset_tags_join RENAME INDEX idx_915bc7a21db69eed TO IDX_DAF7A1EB1DB69EED');
            $this->addSql('ALTER TABLE neos_media_domain_model_asset_tags_join RENAME INDEX idx_915bc7a248d8c57e TO IDX_DAF7A1EB48D8C57E');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_assets_join RENAME INDEX idx_1305d4ce2a965871 TO IDX_E90D72512A965871');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_assets_join RENAME INDEX idx_1305d4ce1db69eed TO IDX_E90D72511DB69EED');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_tags_join RENAME INDEX idx_522f02632a965871 TO IDX_A41705672A965871');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_tags_join RENAME INDEX idx_522f026348d8c57e TO IDX_A417056748D8C57E');
            $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant RENAME INDEX idx_c4bf979f55ff4171 TO IDX_758EDEBD55FF4171');
            $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail RENAME INDEX uniq_3a163c4955ff41717f7cba1a TO originalasset_configurationhash');
            $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail RENAME INDEX uniq_3a163c49bc91f416 TO UNIQ_B7CE1414BC91F416');
            $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail RENAME INDEX idx_3a163c4955ff4171 TO IDX_B7CE141455FF4171');
        } else {
            $this->addSql('ALTER TABLE neos_media_domain_model_adjustment_abstractimageadjustment DROP FOREIGN KEY FK_8B2F26F8A76D06E6');
            $this->addSql('DROP INDEX idx_8b2f26f8a76d06e6 ON neos_media_domain_model_adjustment_abstractimageadjustment');
            $this->addSql('CREATE INDEX IDX_84416FDCA76D06E6 ON neos_media_domain_model_adjustment_abstractimageadjustment (imagevariant)');
            $this->addSql('ALTER TABLE neos_media_domain_model_adjustment_abstractimageadjustment ADD CONSTRAINT FK_8B2F26F8A76D06E6 FOREIGN KEY (imagevariant) REFERENCES neos_media_domain_model_imagevariant (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_media_domain_model_asset DROP FOREIGN KEY FK_675F9550BC91F416');
            $this->addSql('DROP INDEX uniq_675f9550bc91f416 ON neos_media_domain_model_asset');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_B8306B8EBC91F416 ON neos_media_domain_model_asset (resource)');
            $this->addSql('ALTER TABLE neos_media_domain_model_asset ADD CONSTRAINT FK_675F9550BC91F416 FOREIGN KEY (resource) REFERENCES neos_flow_resourcemanagement_persistentresource (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_media_domain_model_asset_tags_join DROP FOREIGN KEY FK_915BC7A21DB69EED');
            $this->addSql('ALTER TABLE neos_media_domain_model_asset_tags_join DROP FOREIGN KEY FK_915BC7A248D8C57E');
            $this->addSql('DROP INDEX idx_915bc7a21db69eed ON neos_media_domain_model_asset_tags_join');
            $this->addSql('CREATE INDEX IDX_DAF7A1EB1DB69EED ON neos_media_domain_model_asset_tags_join (media_asset)');
            $this->addSql('DROP INDEX idx_915bc7a248d8c57e ON neos_media_domain_model_asset_tags_join');
            $this->addSql('CREATE INDEX IDX_DAF7A1EB48D8C57E ON neos_media_domain_model_asset_tags_join (media_tag)');
            $this->addSql('ALTER TABLE neos_media_domain_model_asset_tags_join ADD CONSTRAINT FK_915BC7A21DB69EED FOREIGN KEY (media_asset) REFERENCES neos_media_domain_model_asset (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_media_domain_model_asset_tags_join ADD CONSTRAINT FK_915BC7A248D8C57E FOREIGN KEY (media_tag) REFERENCES neos_media_domain_model_tag (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_assets_join DROP FOREIGN KEY FK_1305D4CE2A965871');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_assets_join DROP FOREIGN KEY FK_1305D4CE1DB69EED');
            $this->addSql('DROP INDEX idx_1305d4ce2a965871 ON neos_media_domain_model_assetcollection_assets_join');
            $this->addSql('CREATE INDEX IDX_E90D72512A965871 ON neos_media_domain_model_assetcollection_assets_join (media_assetcollection)');
            $this->addSql('DROP INDEX idx_1305d4ce1db69eed ON neos_media_domain_model_assetcollection_assets_join');
            $this->addSql('CREATE INDEX IDX_E90D72511DB69EED ON neos_media_domain_model_assetcollection_assets_join (media_asset)');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_assets_join ADD CONSTRAINT FK_1305D4CE2A965871 FOREIGN KEY (media_assetcollection) REFERENCES neos_media_domain_model_assetcollection (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_assets_join ADD CONSTRAINT FK_1305D4CE1DB69EED FOREIGN KEY (media_asset) REFERENCES neos_media_domain_model_asset (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_tags_join DROP FOREIGN KEY FK_522F02632A965871');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_tags_join DROP FOREIGN KEY FK_522F026348D8C57E');
            $this->addSql('DROP INDEX idx_522f02632a965871 ON neos_media_domain_model_assetcollection_tags_join');
            $this->addSql('CREATE INDEX IDX_A41705672A965871 ON neos_media_domain_model_assetcollection_tags_join (media_assetcollection)');
            $this->addSql('DROP INDEX idx_522f026348d8c57e ON neos_media_domain_model_assetcollection_tags_join');
            $this->addSql('CREATE INDEX IDX_A417056748D8C57E ON neos_media_domain_model_assetcollection_tags_join (media_tag)');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_tags_join ADD CONSTRAINT FK_522F02632A965871 FOREIGN KEY (media_assetcollection) REFERENCES neos_media_domain_model_assetcollection (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_tags_join ADD CONSTRAINT FK_522F026348D8C57E FOREIGN KEY (media_tag) REFERENCES neos_media_domain_model_tag (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant DROP FOREIGN KEY FK_C4BF979F55FF4171');
            $this->addSql('DROP INDEX idx_c4bf979f55ff4171 ON neos_media_domain_model_imagevariant');
            $this->addSql('CREATE INDEX IDX_758EDEBD55FF4171 ON neos_media_domain_model_imagevariant (originalasset)');
            $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant ADD CONSTRAINT FK_C4BF979F55FF4171 FOREIGN KEY (originalasset) REFERENCES neos_media_domain_model_image (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail DROP FOREIGN KEY FK_3A163C4955FF4171');
            $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail DROP FOREIGN KEY FK_3A163C49BC91F416');
            $this->addSql('DROP INDEX uniq_3a163c4955ff41717f7cba1a ON neos_media_domain_model_thumbnail');
            $this->addSql('CREATE UNIQUE INDEX originalasset_configurationhash ON neos_media_domain_model_thumbnail (originalasset, configurationhash)');
            $this->addSql('DROP INDEX uniq_3a163c49bc91f416 ON neos_media_domain_model_thumbnail');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_B7CE1414BC91F416 ON neos_media_domain_model_thumbnail (resource)');
            $this->addSql('DROP INDEX idx_3a163c4955ff4171 ON neos_media_domain_model_thumbnail');
            $this->addSql('CREATE INDEX IDX_B7CE141455FF4171 ON neos_media_domain_model_thumbnail (originalasset)');
            $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail ADD CONSTRAINT FK_3A163C4955FF4171 FOREIGN KEY (originalasset) REFERENCES neos_media_domain_model_asset (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail ADD CONSTRAINT FK_3A163C49BC91F416 FOREIGN KEY (resource) REFERENCES neos_flow_resourcemanagement_persistentresource (persistence_object_identifier)');
        }
    }
}
