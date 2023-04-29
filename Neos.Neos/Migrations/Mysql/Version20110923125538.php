<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Fix column names for direct associations
 */
class Version20110923125538 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3_domain_model_domain DROP FOREIGN KEY typo3_typo3_domain_model_domain_ibfk_1");
        $this->addSql("DROP INDEX IDX_64D1A917E12C6E67 ON typo3_typo3_domain_model_domain");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_domain CHANGE typo3_site site VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_domain ADD CONSTRAINT typo3_typo3_domain_model_domain_ibfk_1 FOREIGN KEY (site) REFERENCES typo3_typo3_domain_model_site(flow3_persistence_identifier)");
        $this->addSql("CREATE INDEX IDX_F227E8F6694309E4 ON typo3_typo3_domain_model_domain (site)");

        $this->addSql("ALTER TABLE typo3_typo3_domain_model_media_image DROP FOREIGN KEY typo3_typo3_domain_model_media_image_ibfk_1");
        $this->addSql("DROP INDEX UNIQ_E5EA82E211FFD19F ON typo3_typo3_domain_model_media_image");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_media_image CHANGE flow3_resource_resource resource VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_media_image ADD CONSTRAINT typo3_typo3_domain_model_media_image_ibfk_1 FOREIGN KEY (resource) REFERENCES typo3_flow3_resource_resource(flow3_persistence_identifier)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_E5EA82E2BC91F416 ON typo3_typo3_domain_model_media_image (resource)");

        $this->addSql("ALTER TABLE typo3_typo3_domain_model_user DROP FOREIGN KEY typo3_typo3_domain_model_user_ibfk_1");
        $this->addSql("DROP INDEX UNIQ_5FCB1CAF3210CEC ON typo3_typo3_domain_model_user");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_user CHANGE typo3_userpreferences preferences VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_user ADD CONSTRAINT typo3_typo3_domain_model_user_ibfk_1 FOREIGN KEY (preferences) REFERENCES typo3_typo3_domain_model_userpreferences(flow3_persistence_identifier)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_E3F98B13E931A6F5 ON typo3_typo3_domain_model_user (preferences)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3_domain_model_domain DROP FOREIGN KEY typo3_typo3_domain_model_domain_ibfk_1");
        $this->addSql("DROP INDEX IDX_F227E8F6694309E4 ON typo3_typo3_domain_model_domain");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_domain CHANGE site typo3_site VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_domain ADD CONSTRAINT typo3_typo3_domain_model_domain_ibfk_1 FOREIGN KEY (typo3_site) REFERENCES typo3_typo3_domain_model_site(flow3_persistence_identifier)");
        $this->addSql("CREATE INDEX IDX_64D1A917E12C6E67 ON typo3_typo3_domain_model_domain (typo3_site)");

        $this->addSql("ALTER TABLE typo3_typo3_domain_model_media_image DROP FOREIGN KEY typo3_typo3_domain_model_media_image_ibfk_1");
        $this->addSql("DROP INDEX UNIQ_E5EA82E2BC91F416 ON typo3_typo3_domain_model_media_image");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_media_image CHANGE resource flow3_resource_resource VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_media_image ADD CONSTRAINT typo3_typo3_domain_model_media_image_ibfk_1 FOREIGN KEY (flow3_resource_resource) REFERENCES typo3_flow3_resource_resource(flow3_persistence_identifier)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_E5EA82E211FFD19F ON typo3_typo3_domain_model_media_image (flow3_resource_resource)");

        $this->addSql("ALTER TABLE typo3_typo3_domain_model_user DROP FOREIGN KEY typo3_typo3_domain_model_user_ibfk_1");
        $this->addSql("DROP INDEX UNIQ_E3F98B13E931A6F5 ON typo3_typo3_domain_model_user");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_user CHANGE preferences typo3_userpreferences VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_user ADD CONSTRAINT typo3_typo3_domain_model_user_ibfk_1 FOREIGN KEY (typo3_userpreferences) REFERENCES typo3_typo3_domain_model_userpreferences(flow3_persistence_identifier)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_5FCB1CAF3210CEC ON typo3_typo3_domain_model_user (typo3_userpreferences)");
    }
}
