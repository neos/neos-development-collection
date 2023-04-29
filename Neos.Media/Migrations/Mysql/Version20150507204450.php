<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add asset collections
 */
class Version20150507204450 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("CREATE TABLE typo3_media_domain_model_assetcollection (persistence_object_identifier VARCHAR(40) NOT NULL, title VARCHAR(255) NOT NULL, PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE typo3_media_domain_model_assetcollection_assets_join (media_assetcollection VARCHAR(40) NOT NULL, media_asset VARCHAR(40) NOT NULL, INDEX IDX_E90D72512A965871 (media_assetcollection), INDEX IDX_E90D72511DB69EED (media_asset), PRIMARY KEY(media_assetcollection, media_asset)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE typo3_media_domain_model_assetcollection_tags_join (media_assetcollection VARCHAR(40) NOT NULL, media_tag VARCHAR(40) NOT NULL, INDEX IDX_A41705672A965871 (media_assetcollection), INDEX IDX_A417056748D8C57E (media_tag), PRIMARY KEY(media_assetcollection, media_tag)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE typo3_media_domain_model_assetcollection_assets_join ADD CONSTRAINT FK_E90D72512A965871 FOREIGN KEY (media_assetcollection) REFERENCES typo3_media_domain_model_assetcollection (persistence_object_identifier)");
        $this->addSql("ALTER TABLE typo3_media_domain_model_assetcollection_assets_join ADD CONSTRAINT FK_E90D72511DB69EED FOREIGN KEY (media_asset) REFERENCES typo3_media_domain_model_asset (persistence_object_identifier)");
        $this->addSql("ALTER TABLE typo3_media_domain_model_assetcollection_tags_join ADD CONSTRAINT FK_A41705672A965871 FOREIGN KEY (media_assetcollection) REFERENCES typo3_media_domain_model_assetcollection (persistence_object_identifier)");
        $this->addSql("ALTER TABLE typo3_media_domain_model_assetcollection_tags_join ADD CONSTRAINT FK_A417056748D8C57E FOREIGN KEY (media_tag) REFERENCES typo3_media_domain_model_tag (persistence_object_identifier)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_assetcollection_assets_join DROP FOREIGN KEY FK_E90D72512A965871");
        $this->addSql("ALTER TABLE typo3_media_domain_model_assetcollection_tags_join DROP FOREIGN KEY FK_A41705672A965871");
        $this->addSql("DROP TABLE typo3_media_domain_model_assetcollection");
        $this->addSql("DROP TABLE typo3_media_domain_model_assetcollection_assets_join");
        $this->addSql("DROP TABLE typo3_media_domain_model_assetcollection_tags_join");
    }
}
