<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Rename dtypes in neos_neos_eventlog_domain_model_event
 */
class Version20161125171231 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql("UPDATE neos_neos_eventlog_domain_model_event SET dtype = REPLACE(dtype, 'typo3_neos_', 'neos_neos_')");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql("UPDATE neos_neos_eventlog_domain_model_event SET dtype = REPLACE(dtype, 'neos_neos_', 'typo3_neos_')");
    }
}
