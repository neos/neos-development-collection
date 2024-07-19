<?php

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\AbstractMySQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

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
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform));

        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event CHANGE data data LONGTEXT NOT NULL COMMENT '(DC2Type:flow_json_array)'");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof AbstractMySQLPlatform));

        $this->addSql("ALTER TABLE typo3_neos_eventlog_domain_model_event CHANGE data data LONGTEXT NOT NULL COMMENT '(DC2Type:array)'");
    }
}
