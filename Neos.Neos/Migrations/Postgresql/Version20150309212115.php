<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust DB schema to a clean state (remove cruft that built up in the past)
 */
class Version20150309212115 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        // Doctrine fetches the next value from the sequence itself before persisting, so no nextval() needed
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ALTER uid DROP DEFAULT");

        $this->addSql("ALTER INDEX IF EXISTS idx_f227e8f6694309e4 RENAME TO IDX_8E49A537694309E4"); // typo3_neos_domain_model_domain
        $this->addSql("ALTER INDEX IF EXISTS flow3_identity_typo3_typo3_domain_model_site RENAME TO flow_identity_typo3_neos_domain_model_site"); // typo3_neos_domain_model_site
        $this->addSql("ALTER INDEX IF EXISTS uniq_e3f98b13e931a6f5 RENAME TO UNIQ_FC846DAAE931A6F5"); // typo3_neos_domain_model_user
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("SELECT setval('typo3_neos_eventlog_domain_model_event_uid_seq', (SELECT MAX(uid) FROM typo3_neos_eventlog_domain_model_event))");
        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event ALTER uid SET DEFAULT nextval('typo3_neos_eventlog_domain_model_event_uid_seq')");

        $this->addSql("ALTER INDEX IF EXISTS flow_identity_typo3_neos_domain_model_site RENAME TO flow3_identity_typo3_typo3_domain_model_site"); // typo3_neos_domain_model_site
        $this->addSql("ALTER INDEX IF EXISTS idx_8e49a537694309e4 RENAME TO idx_f227e8f6694309e4"); // typo3_neos_domain_model_domain
        $this->addSql("ALTER INDEX IF EXISTS uniq_fc846daae931a6f5 RENAME TO uniq_e3f98b13e931a6f5"); // typo3_neos_domain_model_user
    }
}
