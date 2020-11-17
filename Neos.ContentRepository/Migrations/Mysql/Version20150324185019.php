<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Migrates TYPO3CR NodeData entries from using serialized data to json encoded data and changes the field type to LONGTEXT afterwards.
 */
class Version20150324185019 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        // If we still have rows containing a Persistence_Object_Identifier in the properties
        // the node migration for the ImageVariants is not yet applied so we have to skip this migration
        // to prevent issues
        $findNonMigratedNodes = $this->connection->query("SELECT * FROM typo3_typo3cr_domain_model_nodedata WHERE properties LIKE '%Persistence_Object_Identifier%'");
        if ($findNonMigratedNodes->rowCount() > 0) {
            $this->abortIf(true, "Stopped the migration as you now have to run a node migration first, and then restart the doctrine:migrate. ./flow node:migrate --version 20141103100401 --confirmation true");
        }

        $select = $this->connection->query("SELECT * FROM typo3_typo3cr_domain_model_nodedata WHERE properties LIKE 'a:%'");
        $nodeData = array();

        while ($result = $select->fetch()) {
            $properties = unserialize($result['properties']);
            $dimensionvalues = unserialize($result['dimensionvalues']);
            $accessroles = unserialize($result['accessroles']);
            $nodeData[] = array(
                'persistence_object_identifier' => $result['persistence_object_identifier'],
                'properties' => json_encode($properties),
                'dimensionvalues' => json_encode($dimensionvalues),
                'accessroles' => json_encode($accessroles),
            );
        }
        $this->connection->beginTransaction();
        $sql = 'UPDATE typo3_typo3cr_domain_model_nodedata SET properties = :properties, dimensionvalues = :dimensionvalues, accessroles = :accessroles WHERE persistence_object_identifier = :persistence_object_identifier';
        foreach ($nodeData as $node) {
            $this->connection->executeQuery($sql, $node);
        }
        $this->connection->commit();

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata CHANGE properties properties LONGTEXT NOT NULL COMMENT '(DC2Type:flow_json_array)'");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata CHANGE dimensionvalues dimensionvalues LONGTEXT NOT NULL COMMENT '(DC2Type:json_array)'");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata CHANGE accessroles accessroles LONGTEXT NOT NULL COMMENT '(DC2Type:json_array)'");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $select = $this->connection->query("SELECT * FROM typo3_typo3cr_domain_model_nodedata");
        $nodeData = array();

        while ($result = $select->fetch()) {
            $properties = json_decode($result['properties'], true);
            $dimensionvalues = json_decode($result['dimensionvalues'], true);
            $accessroles = json_decode($result['accessroles'], true);
            $nodeData[] = array(
                'persistence_object_identifier' => $result['persistence_object_identifier'],
                'properties' => serialize($properties),
                'dimensionvalues' => serialize($dimensionvalues),
                'accessroles' => serialize($accessroles),
            );
        }

        $this->connection->beginTransaction();
        $sql = 'UPDATE typo3_typo3cr_domain_model_nodedata SET properties = :properties, dimensionvalues = :dimensionvalues, accessroles = :accessroles WHERE persistence_object_identifier = :persistence_object_identifier';
        foreach ($nodeData as $node) {
            $this->connection->executeQuery($sql, $node);
        }
        $this->connection->commit();

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata CHANGE properties properties LONGBLOB NOT NULL COMMENT '(DC2Type:objectarray)'");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata CHANGE dimensionvalues dimensionvalues LONGBLOB NOT NULL COMMENT '(DC2Type:objectarray)'");
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata CHANGE accessroles accessroles LONGTEXT NOT NULL COMMENT '(DC2Type:objectarray)'");
    }
}
