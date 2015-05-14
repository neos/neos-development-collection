<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration,
	Doctrine\DBAL\Schema\Schema;

/**
 * Migrates TYPO3CR nodedata properties to use unescaped unicode characters.
 */
class Version20150524150234 extends AbstractMigration {

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function up(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$select = $this->connection->query("SELECT persistence_object_identifier, properties FROM typo3_typo3cr_domain_model_nodedata");

		$update = $this->connection->prepare('UPDATE typo3_typo3cr_domain_model_nodedata SET properties = :properties WHERE persistence_object_identifier = :persistence_object_identifier');
		while ($result = $select->fetch()) {
			$properties = json_decode($result['properties'], TRUE);
			$nodeData = array(
				'persistence_object_identifier' => $result['persistence_object_identifier'],
				'properties' => json_encode($properties, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE),
			);
			$update->execute($nodeData);
		}
	}

	/**
	 * @param Schema $schema
	 * @return void
	 */
	public function down(Schema $schema) {
		$this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

		$select = $this->connection->query("SELECT persistence_object_identifier, properties FROM typo3_typo3cr_domain_model_nodedata");

		$update = $this->connection->prepare('UPDATE typo3_typo3cr_domain_model_nodedata SET properties = :properties WHERE persistence_object_identifier = :persistence_object_identifier');
		while ($result = $select->fetch()) {
			$properties = json_decode($result['properties'], TRUE);
			$nodeData = array(
				'persistence_object_identifier' => $result['persistence_object_identifier'],
				'properties' => json_encode($properties, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT),
			);
			$update->execute($nodeData);
		}
	}
}