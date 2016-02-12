<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Adjust some (old) index names to current Doctrine DBAL behavior (see https://jira.neos.io/browse/FLOW-427)
 */
class Version20160212141523 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("ALTER TABLE typo3_neos_domain_model_domain DROP FOREIGN KEY typo3_neos_domain_model_domain_ibfk_1");
		$this->addSql("DROP INDEX idx_f227e8f6694309e4 ON typo3_neos_domain_model_domain");
		$this->addSql("CREATE INDEX IDX_8E49A537694309E4 ON typo3_neos_domain_model_domain (site)");
		$this->addSql("ALTER TABLE typo3_neos_domain_model_domain ADD CONSTRAINT typo3_neos_domain_model_domain_ibfk_1 FOREIGN KEY (site) REFERENCES typo3_neos_domain_model_site (persistence_object_identifier)");
		$this->addSql("DROP INDEX flow3_identity_typo3_typo3_domain_model_site ON typo3_neos_domain_model_site");
		$this->addSql("CREATE UNIQUE INDEX flow_identity_typo3_neos_domain_model_site ON typo3_neos_domain_model_site (nodename)");
		$this->addSql("ALTER TABLE typo3_neos_domain_model_user DROP FOREIGN KEY typo3_neos_domain_model_user_ibfk_1");
		$this->addSql("DROP INDEX uniq_e3f98b13e931a6f5 ON typo3_neos_domain_model_user");
		$this->addSql("CREATE UNIQUE INDEX UNIQ_FC846DAAE931A6F5 ON typo3_neos_domain_model_user (preferences)");
		$this->addSql("ALTER TABLE typo3_neos_domain_model_user ADD CONSTRAINT typo3_neos_domain_model_user_ibfk_1 FOREIGN KEY (preferences) REFERENCES typo3_neos_domain_model_userpreferences (persistence_object_identifier)");
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("ALTER TABLE typo3_neos_domain_model_domain DROP FOREIGN KEY FK_8E49A537694309E4");
		$this->addSql("DROP INDEX idx_8e49a537694309e4 ON typo3_neos_domain_model_domain");
		$this->addSql("CREATE INDEX IDX_F227E8F6694309E4 ON typo3_neos_domain_model_domain (site)");
		$this->addSql("ALTER TABLE typo3_neos_domain_model_domain ADD CONSTRAINT FK_8E49A537694309E4 FOREIGN KEY (site) REFERENCES typo3_neos_domain_model_site (persistence_object_identifier)");
		$this->addSql("DROP INDEX flow_identity_typo3_neos_domain_model_site ON typo3_neos_domain_model_site");
		$this->addSql("CREATE UNIQUE INDEX flow3_identity_typo3_typo3_domain_model_site ON typo3_neos_domain_model_site (nodename)");
		$this->addSql("ALTER TABLE typo3_neos_domain_model_user DROP FOREIGN KEY FK_FC846DAAE931A6F5");
		$this->addSql("DROP INDEX uniq_fc846daae931a6f5 ON typo3_neos_domain_model_user");
		$this->addSql("CREATE UNIQUE INDEX UNIQ_E3F98B13E931A6F5 ON typo3_neos_domain_model_user (preferences)");
		$this->addSql("ALTER TABLE typo3_neos_domain_model_user ADD CONSTRAINT FK_FC846DAAE931A6F5 FOREIGN KEY (preferences) REFERENCES typo3_neos_domain_model_userpreferences (persistence_object_identifier)");
	}
}