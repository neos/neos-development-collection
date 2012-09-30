<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Rename TYPO3CR tables to follow FQCN
 */
class Version20110824125135 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("RENAME TABLE typo3cr_contentobjectproxy TO typo3_typo3cr_domain_model_contentobjectproxy");
		$this->addSql("RENAME TABLE typo3cr_contenttype TO typo3_typo3cr_domain_model_contenttype");
		$this->addSql("RENAME TABLE typo3cr_node TO typo3_typo3cr_domain_model_node");
		$this->addSql("RENAME TABLE typo3cr_workspace TO typo3_typo3cr_domain_model_workspace");
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("RENAME TABLE typo3_typo3cr_domain_model_contentobjectproxy TO typo3cr_contentobjectproxy");
		$this->addSql("RENAME TABLE typo3_typo3cr_domain_model_contenttype TO typo3cr_contenttype");
		$this->addSql("RENAME TABLE typo3_typo3cr_domain_model_node TO typo3cr_node");
		$this->addSql("RENAME TABLE typo3_typo3cr_domain_model_workspace TO typo3cr_workspace");
	}
}

?>