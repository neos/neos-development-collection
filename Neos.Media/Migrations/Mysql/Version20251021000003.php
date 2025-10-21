<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251021000003 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tables for Neos.Media';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MySQLPlatform'."
        );

        $this->addSql('CREATE TABLE neos_media_domain_model_adjustment_abstractimageadjustment (persistence_object_identifier VARCHAR(40) NOT NULL, imagevariant VARCHAR(40) DEFAULT NULL, position INT NOT NULL, dtype VARCHAR(255) NOT NULL, x INT DEFAULT NULL, y INT DEFAULT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, aspectratioasstring VARCHAR(255) DEFAULT NULL, quality INT DEFAULT NULL, maximumwidth INT DEFAULT NULL, maximumheight INT DEFAULT NULL, minimumwidth INT DEFAULT NULL, minimumheight INT DEFAULT NULL, ratiomode VARCHAR(255) DEFAULT NULL, allowupscaling TINYINT(1) DEFAULT NULL, INDEX IDX_8B2F26F8A76D06E6 (imagevariant), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neos_media_domain_model_asset (persistence_object_identifier VARCHAR(40) NOT NULL, resource VARCHAR(40) DEFAULT NULL, lastmodified DATETIME NOT NULL, title VARCHAR(255) NOT NULL, caption LONGTEXT NOT NULL, copyrightnotice LONGTEXT NOT NULL, assetsourceidentifier VARCHAR(255) NOT NULL, dtype VARCHAR(255) NOT NULL, UNIQUE INDEX UNIQ_675F9550BC91F416 (resource), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neos_media_domain_model_asset_tags_join (media_asset VARCHAR(40) NOT NULL, media_tag VARCHAR(40) NOT NULL, INDEX IDX_915BC7A21DB69EED (media_asset), INDEX IDX_915BC7A248D8C57E (media_tag), PRIMARY KEY(media_asset, media_tag)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neos_media_domain_model_assetcollection (persistence_object_identifier VARCHAR(40) NOT NULL, title VARCHAR(255) NOT NULL, PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neos_media_domain_model_assetcollection_assets_join (media_assetcollection VARCHAR(40) NOT NULL, media_asset VARCHAR(40) NOT NULL, INDEX IDX_1305D4CE2A965871 (media_assetcollection), INDEX IDX_1305D4CE1DB69EED (media_asset), PRIMARY KEY(media_assetcollection, media_asset)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neos_media_domain_model_assetcollection_tags_join (media_assetcollection VARCHAR(40) NOT NULL, media_tag VARCHAR(40) NOT NULL, INDEX IDX_522F02632A965871 (media_assetcollection), INDEX IDX_522F026348D8C57E (media_tag), PRIMARY KEY(media_assetcollection, media_tag)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neos_media_domain_model_audio (persistence_object_identifier VARCHAR(40) NOT NULL, PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neos_media_domain_model_document (persistence_object_identifier VARCHAR(40) NOT NULL, PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neos_media_domain_model_image (persistence_object_identifier VARCHAR(40) NOT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neos_media_domain_model_imagevariant (persistence_object_identifier VARCHAR(40) NOT NULL, originalasset VARCHAR(40) NOT NULL, name VARCHAR(255) NOT NULL, presetidentifier VARCHAR(255) DEFAULT NULL, presetvariantname VARCHAR(255) DEFAULT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, INDEX IDX_C4BF979F55FF4171 (originalasset), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neos_media_domain_model_importedasset (persistence_object_identifier VARCHAR(40) NOT NULL, assetsourceidentifier VARCHAR(255) NOT NULL, remoteassetidentifier VARCHAR(255) NOT NULL, localassetidentifier VARCHAR(255) NOT NULL, importedat DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', localoriginalassetidentifier VARCHAR(255) DEFAULT NULL, UNIQUE INDEX flow_identity_neos_media_domain_model_importedasset (assetsourceidentifier, remoteassetidentifier, localassetidentifier), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neos_media_domain_model_tag (persistence_object_identifier VARCHAR(40) NOT NULL, parent VARCHAR(40) DEFAULT NULL, label VARCHAR(255) NOT NULL, INDEX IDX_CA4889693D8E604F (parent), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neos_media_domain_model_thumbnail (persistence_object_identifier VARCHAR(40) NOT NULL, originalasset VARCHAR(40) NOT NULL, resource VARCHAR(40) DEFAULT NULL, staticresource VARCHAR(255) DEFAULT NULL, configuration JSON NOT NULL COMMENT \'(DC2Type:flow_json_array)\', configurationhash VARCHAR(32) NOT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, quality INT DEFAULT NULL, INDEX IDX_3A163C4955FF4171 (originalasset), UNIQUE INDEX UNIQ_3A163C49BC91F416 (resource), UNIQUE INDEX UNIQ_3A163C4955FF41717F7CBA1A (originalasset, configurationhash), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neos_media_domain_model_video (persistence_object_identifier VARCHAR(40) NOT NULL, width INT NOT NULL, height INT NOT NULL, PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE neos_media_domain_model_adjustment_abstractimageadjustment ADD CONSTRAINT FK_8B2F26F8A76D06E6 FOREIGN KEY (imagevariant) REFERENCES neos_media_domain_model_imagevariant (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neos_media_domain_model_asset ADD CONSTRAINT FK_675F9550BC91F416 FOREIGN KEY (resource) REFERENCES neos_flow_resourcemanagement_persistentresource (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neos_media_domain_model_asset_tags_join ADD CONSTRAINT FK_915BC7A21DB69EED FOREIGN KEY (media_asset) REFERENCES neos_media_domain_model_asset (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neos_media_domain_model_asset_tags_join ADD CONSTRAINT FK_915BC7A248D8C57E FOREIGN KEY (media_tag) REFERENCES neos_media_domain_model_tag (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_assets_join ADD CONSTRAINT FK_1305D4CE2A965871 FOREIGN KEY (media_assetcollection) REFERENCES neos_media_domain_model_assetcollection (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_assets_join ADD CONSTRAINT FK_1305D4CE1DB69EED FOREIGN KEY (media_asset) REFERENCES neos_media_domain_model_asset (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_tags_join ADD CONSTRAINT FK_522F02632A965871 FOREIGN KEY (media_assetcollection) REFERENCES neos_media_domain_model_assetcollection (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_tags_join ADD CONSTRAINT FK_522F026348D8C57E FOREIGN KEY (media_tag) REFERENCES neos_media_domain_model_tag (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neos_media_domain_model_audio ADD CONSTRAINT FK_7D8DF99947A46B0A FOREIGN KEY (persistence_object_identifier) REFERENCES neos_media_domain_model_asset (persistence_object_identifier) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE neos_media_domain_model_document ADD CONSTRAINT FK_20AC4AEA47A46B0A FOREIGN KEY (persistence_object_identifier) REFERENCES neos_media_domain_model_asset (persistence_object_identifier) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE neos_media_domain_model_image ADD CONSTRAINT FK_A0CDCB5347A46B0A FOREIGN KEY (persistence_object_identifier) REFERENCES neos_media_domain_model_asset (persistence_object_identifier) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant ADD CONSTRAINT FK_C4BF979F55FF4171 FOREIGN KEY (originalasset) REFERENCES neos_media_domain_model_image (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant ADD CONSTRAINT FK_C4BF979F47A46B0A FOREIGN KEY (persistence_object_identifier) REFERENCES neos_media_domain_model_asset (persistence_object_identifier) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE neos_media_domain_model_tag ADD CONSTRAINT FK_CA4889693D8E604F FOREIGN KEY (parent) REFERENCES neos_media_domain_model_tag (persistence_object_identifier) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail ADD CONSTRAINT FK_3A163C4955FF4171 FOREIGN KEY (originalasset) REFERENCES neos_media_domain_model_asset (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail ADD CONSTRAINT FK_3A163C49BC91F416 FOREIGN KEY (resource) REFERENCES neos_flow_resourcemanagement_persistentresource (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neos_media_domain_model_video ADD CONSTRAINT FK_1937152047A46B0A FOREIGN KEY (persistence_object_identifier) REFERENCES neos_media_domain_model_asset (persistence_object_identifier) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MySQLPlatform'."
        );

        $this->addSql('ALTER TABLE neos_media_domain_model_adjustment_abstractimageadjustment DROP FOREIGN KEY FK_8B2F26F8A76D06E6');
        $this->addSql('ALTER TABLE neos_media_domain_model_asset DROP FOREIGN KEY FK_675F9550BC91F416');
        $this->addSql('ALTER TABLE neos_media_domain_model_asset_tags_join DROP FOREIGN KEY FK_915BC7A21DB69EED');
        $this->addSql('ALTER TABLE neos_media_domain_model_asset_tags_join DROP FOREIGN KEY FK_915BC7A248D8C57E');
        $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_assets_join DROP FOREIGN KEY FK_1305D4CE2A965871');
        $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_assets_join DROP FOREIGN KEY FK_1305D4CE1DB69EED');
        $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_tags_join DROP FOREIGN KEY FK_522F02632A965871');
        $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection_tags_join DROP FOREIGN KEY FK_522F026348D8C57E');
        $this->addSql('ALTER TABLE neos_media_domain_model_audio DROP FOREIGN KEY FK_7D8DF99947A46B0A');
        $this->addSql('ALTER TABLE neos_media_domain_model_document DROP FOREIGN KEY FK_20AC4AEA47A46B0A');
        $this->addSql('ALTER TABLE neos_media_domain_model_image DROP FOREIGN KEY FK_A0CDCB5347A46B0A');
        $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant DROP FOREIGN KEY FK_C4BF979F55FF4171');
        $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant DROP FOREIGN KEY FK_C4BF979F47A46B0A');
        $this->addSql('ALTER TABLE neos_media_domain_model_tag DROP FOREIGN KEY FK_CA4889693D8E604F');
        $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail DROP FOREIGN KEY FK_3A163C4955FF4171');
        $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail DROP FOREIGN KEY FK_3A163C49BC91F416');
        $this->addSql('ALTER TABLE neos_media_domain_model_video DROP FOREIGN KEY FK_1937152047A46B0A');
        $this->addSql('DROP TABLE neos_media_domain_model_adjustment_abstractimageadjustment');
        $this->addSql('DROP TABLE neos_media_domain_model_asset');
        $this->addSql('DROP TABLE neos_media_domain_model_asset_tags_join');
        $this->addSql('DROP TABLE neos_media_domain_model_assetcollection');
        $this->addSql('DROP TABLE neos_media_domain_model_assetcollection_assets_join');
        $this->addSql('DROP TABLE neos_media_domain_model_assetcollection_tags_join');
        $this->addSql('DROP TABLE neos_media_domain_model_audio');
        $this->addSql('DROP TABLE neos_media_domain_model_document');
        $this->addSql('DROP TABLE neos_media_domain_model_image');
        $this->addSql('DROP TABLE neos_media_domain_model_imagevariant');
        $this->addSql('DROP TABLE neos_media_domain_model_importedasset');
        $this->addSql('DROP TABLE neos_media_domain_model_tag');
        $this->addSql('DROP TABLE neos_media_domain_model_thumbnail');
        $this->addSql('DROP TABLE neos_media_domain_model_video');
    }
}
