<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Adjust some leftover things related to TYPO3CR schema
 */
class Version20160223103919 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema): void  {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

		$this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER pathhash DROP DEFAULT");
		$this->addSql("ALTER INDEX IF EXISTS idx_71de9cfba762b951 RENAME TO IDX_71DE9CFBBB46155");
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema): void  {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

		$this->addSql("ALTER INDEX IF EXISTS idx_71de9cfbbb46155 RENAME TO idx_71de9cfba762b951");
		$this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER pathhash SET DEFAULT ''");
	}
}
