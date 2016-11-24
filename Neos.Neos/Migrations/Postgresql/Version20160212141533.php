<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Adjust some (old) index names to current Doctrine DBAL behavior (see https://jira.neos.io/browse/FLOW-427)
 */
class Version20160212141533 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

		// typo3_neos_domain_model_domain
		$this->addSql("ALTER INDEX IF EXISTS idx_f227e8f6694309e4 RENAME TO IDX_8E49A537694309E4");

		// typo3_neos_domain_model_site
		$this->addSql("ALTER INDEX IF EXISTS flow3_identity_typo3_typo3_domain_model_site RENAME TO flow_identity_typo3_neos_domain_model_site");

		// typo3_neos_domain_model_user
		$this->addSql("ALTER INDEX IF EXISTS uniq_e3f98b13e931a6f5 RENAME TO UNIQ_FC846DAAE931A6F5");
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

		// typo3_neos_domain_model_domain
		$this->addSql("ALTER INDEX IF EXISTS IDX_8E49A537694309E4 RENAME TO idx_f227e8f6694309e4");

		// typo3_neos_domain_model_site
		$this->addSql("ALTER INDEX IF EXISTS flow_identity_typo3_neos_domain_model_site RENAME TO flow3_identity_typo3_typo3_domain_model_site");

		// typo3_neos_domain_model_user
		$this->addSql("ALTER INDEX IF EXISTS UNIQ_FC846DAAE931A6F5 RENAME TO uniq_e3f98b13e931a6f5");
	}
}
