<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add asset collections
 */
class Version20150507204451 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("CREATE TABLE typo3_media_domain_model_assetcollection (persistence_object_identifier VARCHAR(40) NOT NULL, title VARCHAR(255) NOT NULL, PRIMARY KEY(persistence_object_identifier))");
        $this->addSql("CREATE TABLE typo3_media_domain_model_assetcollection_assets_join (media_assetcollection VARCHAR(40) NOT NULL, media_asset VARCHAR(40) NOT NULL, PRIMARY KEY(media_assetcollection, media_asset))");
        $this->addSql("CREATE INDEX IDX_E90D72512A965871 ON typo3_media_domain_model_assetcollection_assets_join (media_assetcollection)");
        $this->addSql("CREATE INDEX IDX_E90D72511DB69EED ON typo3_media_domain_model_assetcollection_assets_join (media_asset)");
        $this->addSql("CREATE TABLE typo3_media_domain_model_assetcollection_tags_join (media_assetcollection VARCHAR(40) NOT NULL, media_tag VARCHAR(40) NOT NULL, PRIMARY KEY(media_assetcollection, media_tag))");
        $this->addSql("CREATE INDEX IDX_A41705672A965871 ON typo3_media_domain_model_assetcollection_tags_join (media_assetcollection)");
        $this->addSql("CREATE INDEX IDX_A417056748D8C57E ON typo3_media_domain_model_assetcollection_tags_join (media_tag)");
        $this->addSql("ALTER TABLE typo3_media_domain_model_assetcollection_assets_join ADD CONSTRAINT FK_E90D72512A965871 FOREIGN KEY (media_assetcollection) REFERENCES typo3_media_domain_model_assetcollection (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE typo3_media_domain_model_assetcollection_assets_join ADD CONSTRAINT FK_E90D72511DB69EED FOREIGN KEY (media_asset) REFERENCES typo3_media_domain_model_asset (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE typo3_media_domain_model_assetcollection_tags_join ADD CONSTRAINT FK_A41705672A965871 FOREIGN KEY (media_assetcollection) REFERENCES typo3_media_domain_model_assetcollection (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE typo3_media_domain_model_assetcollection_tags_join ADD CONSTRAINT FK_A417056748D8C57E FOREIGN KEY (media_tag) REFERENCES typo3_media_domain_model_tag (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_assetcollection_assets_join DROP CONSTRAINT FK_E90D72512A965871");
        $this->addSql("ALTER TABLE typo3_media_domain_model_assetcollection_tags_join DROP CONSTRAINT FK_A41705672A965871");
        $this->addSql("DROP TABLE typo3_media_domain_model_assetcollection");
        $this->addSql("DROP TABLE typo3_media_domain_model_assetcollection_assets_join");
        $this->addSql("DROP TABLE typo3_media_domain_model_assetcollection_tags_join");
    }
}
