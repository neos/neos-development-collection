<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust some (old) index names to current Doctrine DBAL behavior (see https://jira.neos.io/browse/FLOW-427)
 */
class Version20160212141523 extends AbstractMigration
{

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $indexes = $this->sm->listTableIndexes('typo3_neos_domain_model_domain');
        if (array_key_exists('idx_f227e8f6694309e4', $indexes)) {
            $this->addSql("ALTER TABLE typo3_neos_domain_model_domain DROP FOREIGN KEY typo3_neos_domain_model_domain_ibfk_1");
            $this->addSql("DROP INDEX idx_f227e8f6694309e4 ON typo3_neos_domain_model_domain");
            $this->addSql("CREATE INDEX IDX_8E49A537694309E4 ON typo3_neos_domain_model_domain (site)");
            $this->addSql("ALTER TABLE typo3_neos_domain_model_domain ADD CONSTRAINT typo3_neos_domain_model_domain_ibfk_1 FOREIGN KEY (site) REFERENCES typo3_neos_domain_model_site (persistence_object_identifier)");
        }
        $indexes = $this->sm->listTableIndexes('typo3_neos_domain_model_site');
        if (array_key_exists('flow3_identity_typo3_typo3_domain_model_site', $indexes)) {
            $this->addSql("DROP INDEX flow3_identity_typo3_typo3_domain_model_site ON typo3_neos_domain_model_site");
            $this->addSql("CREATE UNIQUE INDEX flow_identity_typo3_neos_domain_model_site ON typo3_neos_domain_model_site (nodename)");
        }
        $indexes = $this->sm->listTableIndexes('typo3_neos_domain_model_user');
        if (array_key_exists('uniq_e3f98b13e931a6f5', $indexes)) {
            $this->addSql("ALTER TABLE typo3_neos_domain_model_user DROP FOREIGN KEY typo3_neos_domain_model_user_ibfk_1");
            $this->addSql("DROP INDEX uniq_e3f98b13e931a6f5 ON typo3_neos_domain_model_user");
            $this->addSql("CREATE UNIQUE INDEX UNIQ_FC846DAAE931A6F5 ON typo3_neos_domain_model_user (preferences)");
            $this->addSql("ALTER TABLE typo3_neos_domain_model_user ADD CONSTRAINT typo3_neos_domain_model_user_ibfk_1 FOREIGN KEY (preferences) REFERENCES typo3_neos_domain_model_userpreferences (persistence_object_identifier)");
        }
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $indexes = $this->sm->listTableIndexes('typo3_neos_domain_model_domain');
        if (array_key_exists('idx_8e49a537694309e4', $indexes)) {
            $this->addSql("ALTER TABLE typo3_neos_domain_model_domain DROP FOREIGN KEY typo3_neos_domain_model_domain_ibfk_1");
            $this->addSql("DROP INDEX idx_8e49a537694309e4 ON typo3_neos_domain_model_domain");
            $this->addSql("CREATE INDEX IDX_F227E8F6694309E4 ON typo3_neos_domain_model_domain (site)");
            $this->addSql("ALTER TABLE typo3_neos_domain_model_domain ADD CONSTRAINT typo3_neos_domain_model_domain_ibfk_1 FOREIGN KEY (site) REFERENCES typo3_neos_domain_model_site (persistence_object_identifier)");
        }
        $indexes = $this->sm->listTableIndexes('typo3_neos_domain_model_site');
        if (array_key_exists('flow_identity_typo3_neos_domain_model_site', $indexes)) {
            $this->addSql("DROP INDEX flow_identity_typo3_neos_domain_model_site ON typo3_neos_domain_model_site");
            $this->addSql("CREATE UNIQUE INDEX flow3_identity_typo3_typo3_domain_model_site ON typo3_neos_domain_model_site (nodename)");
        }
        $indexes = $this->sm->listTableIndexes('typo3_neos_domain_model_user');
        if (array_key_exists('uniq_fc846daae931a6f5', $indexes)) {
            $this->addSql("ALTER TABLE typo3_neos_domain_model_user DROP FOREIGN KEY typo3_neos_domain_model_user_ibfk_1");
            $this->addSql("DROP INDEX uniq_fc846daae931a6f5 ON typo3_neos_domain_model_user");
            $this->addSql("CREATE UNIQUE INDEX UNIQ_E3F98B13E931A6F5 ON typo3_neos_domain_model_user (preferences)");
            $this->addSql("ALTER TABLE typo3_neos_domain_model_user ADD CONSTRAINT typo3_neos_domain_model_user_ibfk_1 FOREIGN KEY (preferences) REFERENCES typo3_neos_domain_model_userpreferences (persistence_object_identifier)");
        }
    }
}
