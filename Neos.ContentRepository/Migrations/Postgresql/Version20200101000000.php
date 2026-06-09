<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Migrates TYPO3CR NodeData entries from using serialized data to json encoded data and changes the field type to JSONB afterwards.
 */
class Version20200101000000 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->connection->exec("COMMENT ON COLUMN neos_contentrepository_domain_model_nodedata.dimensionvalues IS '(DC2Type:flow_json_array)';");
        $this->connection->exec("COMMENT ON COLUMN neos_contentrepository_domain_model_nodedata.accessroles IS '(DC2Type:flow_json_array)';");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->connection->exec("COMMENT ON COLUMN neos_contentrepository_domain_model_nodedata.dimensionvalues IS '(DC2Type:json_array)';");
        $this->connection->exec("COMMENT ON COLUMN neos_contentrepository_domain_model_nodedata.accessroles IS '(DC2Type:json_array)';");
    }
}
