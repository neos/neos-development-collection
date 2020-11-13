<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Migrates TYPO3CR nodedata properties to use unescaped unicode characters.
 */
class Version20150524151134 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");
        if ($this->connection->getWrappedConnection() instanceof \Doctrine\DBAL\Driver\ServerInfoAwareConnection) {
            $version = $this->connection->getWrappedConnection()->getServerVersion();
            // This means we are using a jsonb field (see Version20150324210627) and this already stores unescaped unicode, so we can skip here.
            $this->skipIf(version_compare($version, '9.4', '>='), 'Node properties stored in a JSONB column, no migration necessary.');
        }

        $select = $this->connection->query("SELECT persistence_object_identifier, properties FROM typo3_typo3cr_domain_model_nodedata");

        $update = $this->connection->prepare('UPDATE typo3_typo3cr_domain_model_nodedata SET properties = :properties WHERE persistence_object_identifier = :persistence_object_identifier');
        while ($result = $select->fetch()) {
            $properties = json_decode($result['properties'], true);
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
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");
        if ($this->connection->getWrappedConnection() instanceof \Doctrine\DBAL\Driver\ServerInfoAwareConnection) {
            $version = $this->connection->getWrappedConnection()->getServerVersion();
            // This means we are using a jsonb field (see Version20150324210627) and this already stores unescaped unicode, so we can skip here.
            $this->skipIf(version_compare($version, '9.4', '>='), 'Node properties stored in a JSONB column, no migration necessary.');
        }

        $select = $this->connection->query("SELECT persistence_object_identifier, properties FROM typo3_typo3cr_domain_model_nodedata");

        $update = $this->connection->prepare('UPDATE typo3_typo3cr_domain_model_nodedata SET properties = :properties WHERE persistence_object_identifier = :persistence_object_identifier');
        while ($result = $select->fetch()) {
            $properties = json_decode($result['properties'], true);
            $nodeData = array(
                'persistence_object_identifier' => $result['persistence_object_identifier'],
                'properties' => json_encode($properties, JSON_PRETTY_PRINT | JSON_FORCE_OBJECT),
            );
            $update->execute($nodeData);
        }
    }
}
