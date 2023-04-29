<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Rename Typo3BackendProvider to Neos.Neos:Backend
 */
class Version20170115115240 extends AbstractMigration
{
    /**
     * @return string
     */
    public function getDescription(): string 
    {
        return 'Rename Typo3BackendProvider to Neos.Neos:Backend';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql("UPDATE neos_flow_security_account SET authenticationprovidername = 'Neos.Neos:Backend' WHERE authenticationprovidername = 'Typo3BackendProvider'");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql("UPDATE neos_flow_security_account SET authenticationprovidername = 'Typo3BackendProvider' WHERE authenticationprovidername = 'Neos.Neos:Backend'");
    }
}
