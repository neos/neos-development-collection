<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust column comment to type change
 */
class Version20160223165604 extends AbstractMigration
{

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata CHANGE accessroles accessroles LONGTEXT NOT NULL COMMENT '(DC2Type:flow_json_array)', CHANGE dimensionvalues dimensionvalues LONGTEXT NOT NULL COMMENT '(DC2Type:flow_json_array)'");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata CHANGE dimensionvalues dimensionvalues LONGTEXT NOT NULL COMMENT '(DC2Type:json_array)', CHANGE accessroles accessroles LONGTEXT NOT NULL COMMENT '(DC2Type:json_array)'");
    }
}
