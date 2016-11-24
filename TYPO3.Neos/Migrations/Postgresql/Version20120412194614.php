<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Create tables for PostgreSQL
 */
class Version20120412194614 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("CREATE TABLE typo3_typo3_domain_model_domain (flow3_persistence_identifier VARCHAR(40) NOT NULL, site VARCHAR(40) DEFAULT NULL, hostpattern VARCHAR(255) NOT NULL, PRIMARY KEY(flow3_persistence_identifier))");
        $this->addSql("CREATE INDEX IDX_F227E8F6694309E4 ON typo3_typo3_domain_model_domain (site)");
        $this->addSql("CREATE TABLE typo3_typo3_domain_model_site (flow3_persistence_identifier VARCHAR(40) NOT NULL, name VARCHAR(255) NOT NULL, nodename VARCHAR(255) NOT NULL, state INT NOT NULL, siteresourcespackagekey VARCHAR(255) NOT NULL, PRIMARY KEY(flow3_persistence_identifier))");
        $this->addSql("CREATE TABLE typo3_typo3_domain_model_user (flow3_persistence_identifier VARCHAR(40) NOT NULL, preferences VARCHAR(40) DEFAULT NULL, PRIMARY KEY(flow3_persistence_identifier))");
        $this->addSql("CREATE UNIQUE INDEX UNIQ_E3F98B13E931A6F5 ON typo3_typo3_domain_model_user (preferences)");
        $this->addSql("CREATE TABLE typo3_typo3_domain_model_userpreferences (flow3_persistence_identifier VARCHAR(40) NOT NULL, preferences TEXT NOT NULL, PRIMARY KEY(flow3_persistence_identifier))");
        $this->addSql("COMMENT ON COLUMN typo3_typo3_domain_model_userpreferences.preferences IS '(DC2Type:array)'");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_domain ADD CONSTRAINT FK_F227E8F6694309E4 FOREIGN KEY (site) REFERENCES typo3_typo3_domain_model_site (flow3_persistence_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_user ADD CONSTRAINT FK_E3F98B13E931A6F5 FOREIGN KEY (preferences) REFERENCES typo3_typo3_domain_model_userpreferences (flow3_persistence_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_user ADD CONSTRAINT FK_E3F98B1321E3D446 FOREIGN KEY (flow3_persistence_identifier) REFERENCES typo3_party_domain_model_abstractparty (flow3_persistence_identifier) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3_domain_model_user DROP CONSTRAINT FK_E3F98B1321E3D446");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_domain DROP CONSTRAINT FK_F227E8F6694309E4");
        $this->addSql("ALTER TABLE typo3_typo3_domain_model_user DROP CONSTRAINT FK_E3F98B13E931A6F5");
        $this->addSql("DROP TABLE typo3_typo3_domain_model_domain");
        $this->addSql("DROP TABLE typo3_typo3_domain_model_site");
        $this->addSql("DROP TABLE typo3_typo3_domain_model_user");
        $this->addSql("DROP TABLE typo3_typo3_domain_model_userpreferences");
    }
}
