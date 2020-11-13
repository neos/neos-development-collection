<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Adjust some (old) index names to current Doctrine DBAL behavior (see https://jira.neos.io/browse/FLOW-427)
 */
class Version20160212141534 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema): void  {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

		// typo3_typo3cr_domain_model_nodedata
		$this->addSql("ALTER INDEX IF EXISTS idx_820cadc88d940019 RENAME TO IDX_60A956B98D940019");
		$this->addSql("ALTER INDEX IF EXISTS idx_820cadc84930c33c RENAME TO IDX_60A956B94930C33C");
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema): void  {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

		// typo3_typo3cr_domain_model_nodedata
		$this->addSql("ALTER INDEX IF EXISTS IDX_60A956B98D940019 RENAME TO idx_820cadc88d940019");
		$this->addSql("ALTER INDEX IF EXISTS IDX_60A956B94930C33C RENAME TO idx_820cadc84930c33c");
	}
}
