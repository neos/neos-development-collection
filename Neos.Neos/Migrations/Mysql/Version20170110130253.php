<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Platforms\MySQL57Platform;
use Doctrine\DBAL\Schema\Schema;

class Version20170110130253 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription(): string 
    {
        return 'Adjust foreign key and index names to the renaming of TYPO3.Neos to Neos.Neos';
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
            $this->addSql('ALTER TABLE neos_neos_domain_model_domain RENAME INDEX idx_8e49a537694309e4 TO IDX_51265BE9694309E4');
            $this->addSql('ALTER TABLE neos_neos_domain_model_domain RENAME INDEX flow_identity_typo3_neos_domain_model_domain TO flow_identity_neos_neos_domain_model_domain');
            $this->addSql('ALTER TABLE neos_neos_domain_model_site RENAME INDEX idx_1854b207b8872b4a TO IDX_9B02A4EB8872B4A');
            $this->addSql('ALTER TABLE neos_neos_domain_model_site RENAME INDEX idx_1854b2075ceb2c15 TO IDX_9B02A4E5CEB2C15');
            $this->addSql('ALTER TABLE neos_neos_domain_model_site RENAME INDEX flow_identity_typo3_neos_domain_model_site TO flow_identity_neos_neos_domain_model_site');
            $this->addSql('ALTER TABLE neos_neos_domain_model_user RENAME INDEX uniq_fc846daae931a6f5 TO UNIQ_ED60F5E3E931A6F5');
            $this->addSql('DROP INDEX documentnodeidentifier ON neos_neos_eventlog_domain_model_event');
            $this->addSql('ALTER TABLE neos_neos_eventlog_domain_model_event RENAME INDEX idx_30ab3a75b684c08 TO IDX_D6DBC30A5B684C08');
        } else {
            $this->addSql('ALTER TABLE neos_neos_domain_model_domain DROP FOREIGN KEY neos_neos_domain_model_domain_ibfk_1');
            $this->addSql('DROP INDEX idx_8e49a537694309e4 ON neos_neos_domain_model_domain');
            $this->addSql('CREATE INDEX IDX_51265BE9694309E4 ON neos_neos_domain_model_domain (site)');
            $this->addSql('DROP INDEX flow_identity_typo3_neos_domain_model_domain ON neos_neos_domain_model_domain');
            $this->addSql('CREATE UNIQUE INDEX flow_identity_neos_neos_domain_model_domain ON neos_neos_domain_model_domain (hostname)');
            $this->addSql('ALTER TABLE neos_neos_domain_model_domain ADD CONSTRAINT neos_neos_domain_model_domain_ibfk_1 FOREIGN KEY (site) REFERENCES neos_neos_domain_model_site (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_neos_domain_model_site DROP FOREIGN KEY FK_1854B2075CEB2C15');
            $this->addSql('ALTER TABLE neos_neos_domain_model_site DROP FOREIGN KEY FK_1854B207B8872B4A');
            $this->addSql('DROP INDEX idx_1854b207b8872b4a ON neos_neos_domain_model_site');
            $this->addSql('CREATE INDEX IDX_9B02A4EB8872B4A ON neos_neos_domain_model_site (primarydomain)');
            $this->addSql('DROP INDEX idx_1854b2075ceb2c15 ON neos_neos_domain_model_site');
            $this->addSql('CREATE INDEX IDX_9B02A4E5CEB2C15 ON neos_neos_domain_model_site (assetcollection)');
            $this->addSql('DROP INDEX flow_identity_typo3_neos_domain_model_site ON neos_neos_domain_model_site');
            $this->addSql('CREATE UNIQUE INDEX flow_identity_neos_neos_domain_model_site ON neos_neos_domain_model_site (nodename)');
            $this->addSql('ALTER TABLE neos_neos_domain_model_site ADD CONSTRAINT FK_1854B2075CEB2C15 FOREIGN KEY (assetcollection) REFERENCES neos_media_domain_model_assetcollection (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_neos_domain_model_site ADD CONSTRAINT FK_1854B207B8872B4A FOREIGN KEY (primarydomain) REFERENCES neos_neos_domain_model_domain (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_neos_domain_model_user DROP FOREIGN KEY neos_neos_domain_model_user_ibfk_1');
            $this->addSql('DROP INDEX uniq_fc846daae931a6f5 ON neos_neos_domain_model_user');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_ED60F5E3E931A6F5 ON neos_neos_domain_model_user (preferences)');
            $this->addSql('ALTER TABLE neos_neos_domain_model_user ADD CONSTRAINT neos_neos_domain_model_user_ibfk_1 FOREIGN KEY (preferences) REFERENCES neos_neos_domain_model_userpreferences (persistence_object_identifier)');
            $this->addSql('DROP INDEX documentnodeidentifier ON neos_neos_eventlog_domain_model_event');
            $this->addSql('ALTER TABLE neos_neos_eventlog_domain_model_event DROP FOREIGN KEY FK_30AB3A75B684C08');
            $this->addSql('DROP INDEX idx_30ab3a75b684c08 ON neos_neos_eventlog_domain_model_event');
            $this->addSql('CREATE INDEX IDX_D6DBC30A5B684C08 ON neos_neos_eventlog_domain_model_event (parentevent)');
            $this->addSql('ALTER TABLE neos_neos_eventlog_domain_model_event ADD CONSTRAINT FK_30AB3A75B684C08 FOREIGN KEY (parentevent) REFERENCES neos_neos_eventlog_domain_model_event (uid)');
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
            $this->addSql('ALTER TABLE neos_neos_domain_model_domain RENAME INDEX flow_identity_neos_neos_domain_model_domain TO flow_identity_typo3_neos_domain_model_domain');
            $this->addSql('ALTER TABLE neos_neos_domain_model_domain RENAME INDEX idx_51265be9694309e4 TO IDX_8E49A537694309E4');
            $this->addSql('ALTER TABLE neos_neos_domain_model_site RENAME INDEX flow_identity_neos_neos_domain_model_site TO flow_identity_typo3_neos_domain_model_site');
            $this->addSql('ALTER TABLE neos_neos_domain_model_site RENAME INDEX idx_9b02a4e5ceb2c15 TO IDX_1854B2075CEB2C15');
            $this->addSql('ALTER TABLE neos_neos_domain_model_site RENAME INDEX idx_9b02a4eb8872b4a TO IDX_1854B207B8872B4A');
            $this->addSql('ALTER TABLE neos_neos_domain_model_user RENAME INDEX uniq_ed60f5e3e931a6f5 TO UNIQ_FC846DAAE931A6F5');
            $this->addSql('CREATE INDEX documentnodeidentifier ON neos_neos_eventlog_domain_model_event (documentnodeidentifier)');
            $this->addSql('ALTER TABLE neos_neos_eventlog_domain_model_event RENAME INDEX idx_d6dbc30a5b684c08 TO IDX_30AB3A75B684C08');
        } else {
            $this->addSql('ALTER TABLE neos_neos_domain_model_domain DROP FOREIGN KEY FK_51265BE9694309E4');
            $this->addSql('DROP INDEX flow_identity_neos_neos_domain_model_domain ON neos_neos_domain_model_domain');
            $this->addSql('CREATE UNIQUE INDEX flow_identity_typo3_neos_domain_model_domain ON neos_neos_domain_model_domain (hostname)');
            $this->addSql('DROP INDEX idx_51265be9694309e4 ON neos_neos_domain_model_domain');
            $this->addSql('CREATE INDEX IDX_8E49A537694309E4 ON neos_neos_domain_model_domain (site)');
            $this->addSql('ALTER TABLE neos_neos_domain_model_domain ADD CONSTRAINT FK_51265BE9694309E4 FOREIGN KEY (site) REFERENCES neos_neos_domain_model_site (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_neos_domain_model_site DROP FOREIGN KEY FK_9B02A4EB8872B4A');
            $this->addSql('ALTER TABLE neos_neos_domain_model_site DROP FOREIGN KEY FK_9B02A4E5CEB2C15');
            $this->addSql('DROP INDEX flow_identity_neos_neos_domain_model_site ON neos_neos_domain_model_site');
            $this->addSql('CREATE UNIQUE INDEX flow_identity_typo3_neos_domain_model_site ON neos_neos_domain_model_site (nodename)');
            $this->addSql('DROP INDEX idx_9b02a4e5ceb2c15 ON neos_neos_domain_model_site');
            $this->addSql('CREATE INDEX IDX_1854B2075CEB2C15 ON neos_neos_domain_model_site (assetcollection)');
            $this->addSql('DROP INDEX idx_9b02a4eb8872b4a ON neos_neos_domain_model_site');
            $this->addSql('CREATE INDEX IDX_1854B207B8872B4A ON neos_neos_domain_model_site (primarydomain)');
            $this->addSql('ALTER TABLE neos_neos_domain_model_site ADD CONSTRAINT FK_9B02A4EB8872B4A FOREIGN KEY (primarydomain) REFERENCES neos_neos_domain_model_domain (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_neos_domain_model_site ADD CONSTRAINT FK_9B02A4E5CEB2C15 FOREIGN KEY (assetcollection) REFERENCES neos_media_domain_model_assetcollection (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_neos_domain_model_user DROP FOREIGN KEY FK_ED60F5E3E931A6F5');
            $this->addSql('DROP INDEX uniq_ed60f5e3e931a6f5 ON neos_neos_domain_model_user');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_FC846DAAE931A6F5 ON neos_neos_domain_model_user (preferences)');
            $this->addSql('ALTER TABLE neos_neos_domain_model_user ADD CONSTRAINT FK_ED60F5E3E931A6F5 FOREIGN KEY (preferences) REFERENCES neos_neos_domain_model_userpreferences (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_neos_eventlog_domain_model_event DROP FOREIGN KEY FK_D6DBC30A5B684C08');
            $this->addSql('CREATE INDEX documentnodeidentifier ON neos_neos_eventlog_domain_model_event (documentnodeidentifier)');
            $this->addSql('DROP INDEX idx_d6dbc30a5b684c08 ON neos_neos_eventlog_domain_model_event');
            $this->addSql('CREATE INDEX IDX_30AB3A75B684C08 ON neos_neos_eventlog_domain_model_event (parentevent)');
            $this->addSql('ALTER TABLE neos_neos_eventlog_domain_model_event ADD CONSTRAINT FK_D6DBC30A5B684C08 FOREIGN KEY (parentevent) REFERENCES neos_neos_eventlog_domain_model_event (uid)');
        }
    }
}
