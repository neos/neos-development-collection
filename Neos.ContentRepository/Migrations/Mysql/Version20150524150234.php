<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Migrates TYPO3CR nodedata properties to use unescaped unicode characters.
 */
class Version20150524150234 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $select = $this->connection->query("SELECT persistence_object_identifier, properties FROM typo3_typo3cr_domain_model_nodedata");

        while ($result = $select->fetch()) {
            $properties = json_decode($result['properties'], true);
            $nodeData = array(
                'persistence_object_identifier' => $result['persistence_object_identifier'],
                'properties' => json_encode($properties, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT | JSON_UNESCAPED_UNICODE),
            );
            $sql = 'UPDATE typo3_typo3cr_domain_model_nodedata SET properties = :properties WHERE persistence_object_identifier = :persistence_object_identifier';
            $this->connection->executeQuery($sql, $nodeData);
        }
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $select = $this->connection->query("SELECT persistence_object_identifier, properties FROM typo3_typo3cr_domain_model_nodedata");

        while ($result = $select->fetch()) {
            $properties = json_decode($result['properties'], true);
            $nodeData = array(
                'persistence_object_identifier' => $result['persistence_object_identifier'],
                'properties' => json_encode($properties, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT),
            );
            $sql = 'UPDATE typo3_typo3cr_domain_model_nodedata SET properties = :properties WHERE persistence_object_identifier = :persistence_object_identifier';
            $this->connection->executeQuery($sql, $nodeData);
        }
    }
}
