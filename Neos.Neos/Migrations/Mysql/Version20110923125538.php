<?php

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

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
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform));

        foreach ($this->sm->listTableForeignKeys('typo3_typo3_domain_model_domain') as $foreignKey) {
            if (in_array('typo3_site', array_map('strtolower', $foreignKey->getLocalColumns()), true)) {
                $this->addSql("ALTER TABLE typo3_typo3_domain_model_domain DROP FOREIGN KEY " . $foreignKey->getName());
            }
        }
        $indexes = $this->sm->listTableIndexes('typo3_typo3_domain_model_domain');
        if (array_key_exists('idx_64d1a917e12c6e67', $indexes)) {
            $this->addSql("DROP INDEX IDX_64D1A917E12C6E67 ON typo3_typo3_domain_model_domain");
        }
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_domain CHANGE typo3_site site VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_domain ADD CONSTRAINT typo3_typo3_domain_model_domain_ibfk_1 FOREIGN KEY (site) REFERENCES typo3_typo3_domain_model_site(flow3_persistence_identifier)");
        $this->addSql("CREATE INDEX IDX_F227E8F6694309E4 ON typo3_typo3_domain_model_domain (site)");

        foreach ($this->sm->listTableForeignKeys('typo3_typo3_domain_model_media_image') as $foreignKey) {
            if (in_array('flow3_resource_resource', array_map('strtolower', $foreignKey->getLocalColumns()), true)) {
                $this->addSql("ALTER TABLE typo3_typo3_domain_model_media_image DROP FOREIGN KEY " . $foreignKey->getName());
            }
        }
        $indexes = $this->sm->listTableIndexes('typo3_typo3_domain_model_media_image');
        if (array_key_exists('uniq_e5ea82e211ffd19f', $indexes)) {
            $this->addSql("DROP INDEX UNIQ_E5EA82E211FFD19F ON typo3_typo3_domain_model_media_image");
        }
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_media_image CHANGE flow3_resource_resource resource VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_media_image ADD CONSTRAINT typo3_typo3_domain_model_media_image_ibfk_1 FOREIGN KEY (resource) REFERENCES typo3_flow3_resource_resource(flow3_persistence_identifier)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_E5EA82E2BC91F416 ON typo3_typo3_domain_model_media_image (resource)");

        foreach ($this->sm->listTableForeignKeys('typo3_typo3_domain_model_user') as $foreignKey) {
            if (in_array('typo3_userpreferences', array_map('strtolower', $foreignKey->getLocalColumns()), true)) {
                $this->addSql("ALTER TABLE typo3_typo3_domain_model_user DROP FOREIGN KEY " . $foreignKey->getName());
            }
        }
        $indexes = $this->sm->listTableIndexes('typo3_typo3_domain_model_user');
        if (array_key_exists('uniq_5fcb1caf3210cec', $indexes)) {
            $this->addSql("DROP INDEX UNIQ_5FCB1CAF3210CEC ON typo3_typo3_domain_model_user");
        }
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
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform));

        foreach ($this->sm->listTableForeignKeys('typo3_typo3_domain_model_domain') as $foreignKey) {
            if (in_array('site', array_map('strtolower', $foreignKey->getLocalColumns()), true)) {
                $this->addSql("ALTER TABLE typo3_typo3_domain_model_domain DROP FOREIGN KEY " . $foreignKey->getName());
            }
        }
        $indexes = $this->sm->listTableIndexes('typo3_typo3_domain_model_domain');
        if (array_key_exists('idx_f227e8f6694309e4', $indexes)) {
            $this->addSql("DROP INDEX IDX_F227E8F6694309E4 ON typo3_typo3_domain_model_domain");
        }
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_domain CHANGE site typo3_site VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_domain ADD CONSTRAINT typo3_typo3_domain_model_domain_ibfk_1 FOREIGN KEY (typo3_site) REFERENCES typo3_typo3_domain_model_site(flow3_persistence_identifier)");
        $this->addSql("CREATE INDEX IDX_64D1A917E12C6E67 ON typo3_typo3_domain_model_domain (typo3_site)");

        foreach ($this->sm->listTableForeignKeys('typo3_typo3_domain_model_media_image') as $foreignKey) {
            if (in_array('resource', array_map('strtolower', $foreignKey->getLocalColumns()), true)) {
                $this->addSql("ALTER TABLE typo3_typo3_domain_model_media_image DROP FOREIGN KEY " . $foreignKey->getName());
            }
        }
        $indexes = $this->sm->listTableIndexes('typo3_typo3_domain_model_media_image');
        if (array_key_exists('uniq_e5ea82e2bc91f416', $indexes)) {
            $this->addSql("DROP INDEX UNIQ_E5EA82E2BC91F416 ON typo3_typo3_domain_model_media_image");
        }
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_media_image CHANGE resource flow3_resource_resource VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_media_image ADD CONSTRAINT typo3_typo3_domain_model_media_image_ibfk_1 FOREIGN KEY (flow3_resource_resource) REFERENCES typo3_flow3_resource_resource(flow3_persistence_identifier)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_E5EA82E211FFD19F ON typo3_typo3_domain_model_media_image (flow3_resource_resource)");

        foreach ($this->sm->listTableForeignKeys('typo3_typo3_domain_model_user') as $foreignKey) {
            if (in_array('preferences', array_map('strtolower', $foreignKey->getLocalColumns()), true)) {
                $this->addSql("ALTER TABLE typo3_typo3_domain_model_user DROP FOREIGN KEY " . $foreignKey->getName());
            }
        }
        $indexes = $this->sm->listTableIndexes('typo3_typo3_domain_model_user');
        if (array_key_exists('uniq_e3f98b13e931a6f5', $indexes)) {
            $this->addSql("DROP INDEX UNIQ_E3F98B13E931A6F5 ON typo3_typo3_domain_model_user");
        }
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_user CHANGE preferences typo3_userpreferences VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_user ADD CONSTRAINT typo3_typo3_domain_model_user_ibfk_1 FOREIGN KEY (typo3_userpreferences) REFERENCES typo3_typo3_domain_model_userpreferences(flow3_persistence_identifier)");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_5FCB1CAF3210CEC ON typo3_typo3_domain_model_user (typo3_userpreferences)");
    }
}
