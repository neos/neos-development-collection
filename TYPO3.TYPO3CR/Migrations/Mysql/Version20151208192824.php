<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Adds indices to improve query speed for variant overlay queries
 */
class Version20151208192824 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata CHANGE identifier identifier VARCHAR(36) DEFAULT NULL");
		$this->addSql("CREATE INDEX parentpath_workspace_idindex ON typo3_typo3cr_domain_model_nodedata (parentpathhash, workspace, identifier)");
		$this->addSql("CREATE INDEX path_workspace_idindex ON typo3_typo3cr_domain_model_nodedata (pathhash, workspace, identifier)");
		$this->addSql("CREATE INDEX workspace_idindex ON typo3_typo3cr_domain_model_nodedata (workspace, identifier)");
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("DROP INDEX path_workspace_idindex ON typo3_typo3cr_domain_model_nodedata");
		$this->addSql("DROP INDEX parentpath_workspace_idindex ON typo3_typo3cr_domain_model_nodedata");
		$this->addSql("DROP INDEX workspace_idindex ON typo3_typo3cr_domain_model_nodedata");
	}
}