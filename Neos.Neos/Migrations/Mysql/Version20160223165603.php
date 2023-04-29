<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust column comment to type change
 */
class Version20160223165603 extends AbstractMigration
{

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event CHANGE data data LONGTEXT NOT NULL COMMENT '(DC2Type:flow_json_array)'");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event CHANGE data data LONGTEXT NOT NULL COMMENT '(DC2Type:array)'");
    }
}
