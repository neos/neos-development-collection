<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjustments for the rewritten Flow resource management and the new domain model structure
 */
class Version20141118174900 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("CREATE TABLE typo3_media_domain_model_adjustment_abstractimageadjustment (persistence_object_identifier VARCHAR(40) NOT NULL, imagevariant VARCHAR(40) DEFAULT NULL, dtype VARCHAR(255) NOT NULL, x INT DEFAULT NULL, y INT DEFAULT NULL, width INT DEFAULT NULL, height INT DEFAULT NULL, maximumwidth INT DEFAULT NULL, maximumheight INT DEFAULT NULL, minimumwidth INT DEFAULT NULL, minimumheight INT DEFAULT NULL, ratiomode INT DEFAULT NULL, PRIMARY KEY(persistence_object_identifier))");
        $this->addSql("CREATE INDEX IDX_84416FDCA76D06E6 ON typo3_media_domain_model_adjustment_abstractimageadjustment (imagevariant)");
        $this->addSql("CREATE TABLE typo3_media_domain_model_imagevariant (persistence_object_identifier VARCHAR(40) NOT NULL, originalasset VARCHAR(40) DEFAULT NULL, name VARCHAR(255) NOT NULL, width INT NOT NULL, height INT NOT NULL, PRIMARY KEY(persistence_object_identifier))");
        $this->addSql("CREATE INDEX IDX_758EDEBD55FF4171 ON typo3_media_domain_model_imagevariant (originalasset)");
        $this->addSql("CREATE TABLE typo3_media_domain_model_thumbnail (persistence_object_identifier VARCHAR(40) NOT NULL, originalasset VARCHAR(40) DEFAULT NULL, resource VARCHAR(40) DEFAULT NULL, maximumwidth INT DEFAULT NULL, maximumheight INT DEFAULT NULL, ratiomode INT NOT NULL, width INT NOT NULL, height INT NOT NULL, PRIMARY KEY(persistence_object_identifier))");
        $this->addSql("CREATE INDEX IDX_B7CE141455FF4171 ON typo3_media_domain_model_thumbnail (originalasset)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_B7CE1414BC91F416 ON typo3_media_domain_model_thumbnail (resource)");
        $this->addSql("ALTER TABLE typo3_media_domain_model_adjustment_abstractimageadjustment ADD CONSTRAINT FK_84416FDCA76D06E6 FOREIGN KEY (imagevariant) REFERENCES typo3_media_domain_model_imagevariant (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE typo3_media_domain_model_imagevariant ADD CONSTRAINT FK_758EDEBD55FF4171 FOREIGN KEY (originalasset) REFERENCES typo3_media_domain_model_image (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE typo3_media_domain_model_imagevariant ADD CONSTRAINT FK_758EDEBD47A46B0A FOREIGN KEY (persistence_object_identifier) REFERENCES typo3_media_domain_model_asset (persistence_object_identifier) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail ADD CONSTRAINT FK_B7CE141455FF4171 FOREIGN KEY (originalasset) REFERENCES typo3_media_domain_model_asset (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail ADD CONSTRAINT FK_B7CE1414BC91F416 FOREIGN KEY (resource) REFERENCES typo3_flow_resource_resource (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("DROP INDEX idx_b8306b8ebc91f416");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_B8306B8EBC91F416 ON typo3_media_domain_model_asset (resource)");
        $this->addSql("ALTER TABLE typo3_media_domain_model_image DROP type");
        $this->addSql("ALTER TABLE typo3_media_domain_model_image DROP imagevariants");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_adjustment_abstractimageadjustment DROP CONSTRAINT FK_84416FDCA76D06E6");
        $this->addSql("DROP TABLE typo3_media_domain_model_adjustment_abstractimageadjustment");
        $this->addSql("DROP TABLE typo3_media_domain_model_imagevariant");
        $this->addSql("DROP TABLE typo3_media_domain_model_thumbnail");
        $this->addSql("ALTER TABLE typo3_media_domain_model_image ADD type INT NOT NULL");
        $this->addSql("ALTER TABLE typo3_media_domain_model_image ADD imagevariants TEXT NOT NULL");
        $this->addSql("COMMENT ON COLUMN typo3_media_domain_model_image.imagevariants IS '(DC2Type:array)'");
        $this->addSql("DROP INDEX UNIQ_B8306B8EBC91F416");
        $this->addSql("CREATE INDEX idx_b8306b8ebc91f416 ON typo3_media_domain_model_asset (resource)");
    }
}
