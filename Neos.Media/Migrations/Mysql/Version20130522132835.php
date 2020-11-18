<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add tags to assets
 */
class Version20130522132835 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("CREATE TABLE typo3_media_domain_model_asset_tags_join (media_asset VARCHAR(40) NOT NULL, media_tag VARCHAR(40) NOT NULL, INDEX IDX_DAF7A1EB1DB69EED (media_asset), INDEX IDX_DAF7A1EB48D8C57E (media_tag), PRIMARY KEY(media_asset, media_tag)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE typo3_media_domain_model_tag (persistence_object_identifier VARCHAR(40) NOT NULL, `label` VARCHAR(255) NOT NULL, PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE typo3_media_domain_model_asset_tags_join ADD CONSTRAINT FK_DAF7A1EB1DB69EED FOREIGN KEY (media_asset) REFERENCES typo3_media_domain_model_asset (persistence_object_identifier)");
        $this->addSql("ALTER TABLE typo3_media_domain_model_asset_tags_join ADD CONSTRAINT FK_DAF7A1EB48D8C57E FOREIGN KEY (media_tag) REFERENCES typo3_media_domain_model_tag (persistence_object_identifier)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_asset_tags_join DROP FOREIGN KEY FK_DAF7A1EB48D8C57E");
        $this->addSql("DROP TABLE typo3_media_domain_model_asset_tags_join");
        $this->addSql("DROP TABLE typo3_media_domain_model_tag");
    }
}
