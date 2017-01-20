<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Rename Typo3BackendProvider to Neos.Neos:Default
 */
class Version20170115115240 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Rename Typo3BackendProvider to Neos.Neos:Default';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql("UPDATE neos_flow_security_account SET authenticationprovidername = 'Neos.Neos:Default' WHERE authenticationprovidername = 'Typo3BackendProvider'");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql("UPDATE neos_flow_security_account SET authenticationprovidername = 'Typo3BackendProvider' WHERE authenticationprovidername = 'Neos.Neos:Default'");
    }
}
