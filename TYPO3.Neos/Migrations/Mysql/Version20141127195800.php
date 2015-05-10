<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Resolve issue with Doctrine collection object property name change for serialized image properties in node data
 */
class Version20141127195800 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("UPDATE typo3_typo3cr_domain_model_nodedata SET properties = REPLACE(properties, '{s:53:\"\0Doctrine\\\Common\\\Collections\\\ArrayCollection\0elements\"', '{s:54:\"\0Doctrine\\\Common\\\Collections\\\ArrayCollection\0_elements\"')");
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$this->addSql("UPDATE typo3_typo3cr_domain_model_nodedata SET properties = REPLACE(properties, '{s:54:\"\0Doctrine\\\Common\\\Collections\\\ArrayCollection\0_elements\"', '{s:53:\"\0Doctrine\\\Common\\\Collections\\\ArrayCollection\0elements\"')");
	}
}