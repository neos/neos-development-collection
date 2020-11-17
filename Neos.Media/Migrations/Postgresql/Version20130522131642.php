<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust Neos.Media tables to new Asset base class and add new models.
 */
class Version20130522131642 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

            // new tables for Asset, Document, Video, Audio
        $this->addSql("CREATE TABLE typo3_media_domain_model_asset (persistence_object_identifier VARCHAR(40) NOT NULL, resource VARCHAR(40) DEFAULT NULL, title VARCHAR(255) NOT NULL, dtype VARCHAR(255) NOT NULL, PRIMARY KEY(persistence_object_identifier))");
        $this->addSql("CREATE INDEX IDX_B8306B8EBC91F416 ON typo3_media_domain_model_asset (resource)");
        $this->addSql("CREATE TABLE typo3_media_domain_model_audio (persistence_object_identifier VARCHAR(40) NOT NULL, PRIMARY KEY(persistence_object_identifier))");
        $this->addSql("CREATE TABLE typo3_media_domain_model_document (persistence_object_identifier VARCHAR(40) NOT NULL, PRIMARY KEY(persistence_object_identifier))");
        $this->addSql("CREATE TABLE typo3_media_domain_model_video (persistence_object_identifier VARCHAR(40) NOT NULL, width INT NOT NULL, height INT NOT NULL, PRIMARY KEY(persistence_object_identifier))");
        $this->addSql("ALTER TABLE typo3_media_domain_model_asset ADD CONSTRAINT FK_B8306B8EBC91F416 FOREIGN KEY (resource) REFERENCES typo3_flow_resource_resource (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE typo3_media_domain_model_audio ADD CONSTRAINT FK_A2E2074747A46B0A FOREIGN KEY (persistence_object_identifier) REFERENCES typo3_media_domain_model_asset (persistence_object_identifier) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE typo3_media_domain_model_document ADD CONSTRAINT FK_F089E2F547A46B0A FOREIGN KEY (persistence_object_identifier) REFERENCES typo3_media_domain_model_asset (persistence_object_identifier) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE typo3_media_domain_model_video ADD CONSTRAINT FK_C658EBFE47A46B0A FOREIGN KEY (persistence_object_identifier) REFERENCES typo3_media_domain_model_asset (persistence_object_identifier) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");

            // populate Asset table with existing Image data
        $this->addSql("INSERT INTO typo3_media_domain_model_asset (persistence_object_identifier, dtype, resource, title) SELECT persistence_object_identifier, 'typo3_media_image', resource, title FROM typo3_media_domain_model_image");

            // adjust Image table
        $this->addSql("ALTER TABLE typo3_media_domain_model_image DROP resource");
        $this->addSql("ALTER TABLE typo3_media_domain_model_image DROP title");
        $this->addSql("ALTER TABLE typo3_media_domain_model_image ADD CONSTRAINT FK_7FA2358D47A46B0A FOREIGN KEY (persistence_object_identifier) REFERENCES typo3_media_domain_model_asset (persistence_object_identifier) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

            // adjust Image table
        $this->addSql("ALTER TABLE typo3_media_domain_model_image ADD resource VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_media_domain_model_image ADD title VARCHAR(255) NOT NULL");
        $this->addSql("ALTER TABLE typo3_media_domain_model_image ADD CONSTRAINT fk_7fa2358dbc91f416 FOREIGN KEY (resource) REFERENCES typo3_flow_resource_resource (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("CREATE INDEX idx_7fa2358dbc91f416 ON typo3_media_domain_model_image (resource)");

            // populate Image table with existing data
        $this->addSql("UPDATE typo3_media_domain_model_image AS image SET resource = (SELECT resource FROM typo3_media_domain_model_asset AS asset WHERE image.persistence_object_identifier = asset.persistence_object_identifier), title = (SELECT title FROM typo3_media_domain_model_asset AS asset WHERE image.persistence_object_identifier = asset.persistence_object_identifier)");

            // drop tables for Document, Video, Audio, Asset
        $this->addSql("ALTER TABLE typo3_media_domain_model_audio DROP CONSTRAINT FK_A2E2074747A46B0A");
        $this->addSql("ALTER TABLE typo3_media_domain_model_document DROP CONSTRAINT FK_F089E2F547A46B0A");
        $this->addSql("ALTER TABLE typo3_media_domain_model_image DROP CONSTRAINT FK_7FA2358D47A46B0A");
        $this->addSql("ALTER TABLE typo3_media_domain_model_video DROP CONSTRAINT FK_C658EBFE47A46B0A");

        $this->addSql("DROP TABLE typo3_media_domain_model_asset");
        $this->addSql("DROP TABLE typo3_media_domain_model_audio");
        $this->addSql("DROP TABLE typo3_media_domain_model_document");
        $this->addSql("DROP TABLE typo3_media_domain_model_video");
    }
}
