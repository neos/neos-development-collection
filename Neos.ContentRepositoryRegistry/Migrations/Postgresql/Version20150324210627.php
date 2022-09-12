<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Migrates TYPO3CR NodeData entries from using serialized data to json encoded data and changes the field type to JSONB afterwards.
 */
class Version20150324210627 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        // If we still have rows containing a Persistence_Object_Identifier in the properties
        // the node migration for the ImageVariants is not yet applied so we have to skip this migration
        // to prevent issues
        $findNonMigratedNodes = $this->connection->query("SELECT * FROM typo3_typo3cr_domain_model_nodedata WHERE properties LIKE '%Persistence_Object_Identifier%'");
        if ($findNonMigratedNodes->rowCount() > 0) {
            $this->abortIf(true, "Stopped the migration as you now have to run a node migration first, and then restart the doctrine:migrate. ./flow node:migrate --version 20141103100401 --confirmation true");
        }

        $jsonFieldType = $this->connection->getDatabasePlatform()->getJsonTypeDeclarationSQL(array());
        if ($this->connection->getWrappedConnection() instanceof \Doctrine\DBAL\Driver\ServerInfoAwareConnection) {
            $version = $this->connection->getWrappedConnection()->getServerVersion();
            if (version_compare($version, '9.4', '>=')) {
                $jsonFieldType = 'jsonb';
            }
        }

        $select = $this->connection->query("SELECT * FROM typo3_typo3cr_domain_model_nodedata WHERE properties LIKE '" . bin2hex('a:') . "%'");
        $nodeData = array();
        while ($result = $select->fetch()) {
            $properties = unserialize(hex2bin((is_resource($result['properties'])) ? stream_get_contents($result['properties']) : $result['properties']));
            $dimensionvalues = unserialize(hex2bin((is_resource($result['dimensionvalues'])) ? stream_get_contents($result['dimensionvalues']) : $result['dimensionvalues']));
            $accessroles = unserialize((is_resource($result['accessroles'])) ? stream_get_contents($result['accessroles']) : $result['accessroles']);
            $nodeData[] = array(
                'persistence_object_identifier' => $result['persistence_object_identifier'],
                'properties' => json_encode($properties),
                'dimensionvalues' => json_encode($dimensionvalues),
                'accessroles' => json_encode($accessroles),
            );
        }

        $this->connection->exec('BEGIN;');
        $this->connection->exec("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD COLUMN jproperties " . $jsonFieldType . ";");
        $this->connection->exec("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD COLUMN jdimensionvalues " . $jsonFieldType . ";");
        $this->connection->exec("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD COLUMN jaccessroles " . $jsonFieldType . ";");

        $update = $this->connection->prepare('UPDATE typo3_typo3cr_domain_model_nodedata SET jproperties = :properties, jdimensionvalues = :dimensionvalues, jaccessroles = :accessroles WHERE persistence_object_identifier = :persistence_object_identifier');
        foreach ($nodeData as $node) {
            $update->execute($node);
        }

        $this->connection->exec('COMMIT;');

        $this->connection->exec("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP properties;");
        $this->connection->exec("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP dimensionvalues;");
        $this->connection->exec("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP accessroles;");

        $this->connection->exec("ALTER TABLE typo3_typo3cr_domain_model_nodedata RENAME jproperties TO properties;");
        $this->connection->exec("COMMENT ON COLUMN typo3_typo3cr_domain_model_nodedata.properties IS '(DC2Type:flow_json_array)';");
        $this->connection->exec("ALTER TABLE typo3_typo3cr_domain_model_nodedata RENAME jdimensionvalues TO dimensionvalues;");
        $this->connection->exec("COMMENT ON COLUMN typo3_typo3cr_domain_model_nodedata.dimensionvalues IS '(DC2Type:json_array)';");
        $this->connection->exec("ALTER TABLE typo3_typo3cr_domain_model_nodedata RENAME jaccessroles TO accessroles;");
        $this->connection->exec("COMMENT ON COLUMN typo3_typo3cr_domain_model_nodedata.accessroles IS '(DC2Type:json_array)';");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

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

        $this->connection->exec('BEGIN;');
        $this->connection->exec("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD COLUMN aproperties BYTEA;");
        $this->connection->exec("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD COLUMN adimensionvalues BYTEA");
        $this->connection->exec("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD COLUMN aaccessroles BYTEA");

        $update = $this->connection->prepare('UPDATE typo3_typo3cr_domain_model_nodedata SET aproperties = :properties, adimensionvalues = :dimensionvalues, aaccessroles = :accessroles WHERE persistence_object_identifier = :persistence_object_identifier');
        foreach ($nodeData as $node) {
            $update->execute($node);
        }
        $this->connection->exec('COMMIT;');

        $this->connection->exec("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP COLUMN properties;");
        $this->connection->exec("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP COLUMN dimensionvalues;");
        $this->connection->exec("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP COLUMN accessroles;");

        $this->connection->exec("ALTER TABLE typo3_typo3cr_domain_model_nodedata RENAME aproperties TO properties;");
        $this->connection->exec("COMMENT ON COLUMN typo3_typo3cr_domain_model_nodedata.properties IS '(DC2Type:objectarray)';");
        $this->connection->exec("ALTER TABLE typo3_typo3cr_domain_model_nodedata RENAME adimensionvalues TO dimensionvalues;");
        $this->connection->exec("COMMENT ON COLUMN typo3_typo3cr_domain_model_nodedata.dimensionvalues IS '(DC2Type:objectarray)';");
        $this->connection->exec("ALTER TABLE typo3_typo3cr_domain_model_nodedata RENAME aaccessroles TO accessroles;");
        $this->connection->exec("COMMENT ON COLUMN typo3_typo3cr_domain_model_nodedata.accessroles IS '(DC2Type:objectarray)';");
    }
}
