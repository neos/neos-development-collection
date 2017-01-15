<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Rename Typo3BackendProvider to NeosDefaultProvider
 */
class Version20170115114801 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql("UPDATE neos_flow_security_account SET authenticationprovidername = 'NeosDefaultProvider' WHERE authenticationprovidername = 'Typo3BackendProvider'");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql("UPDATE neos_flow_security_account SET authenticationprovidername = 'Typo3BackendProvider' WHERE authenticationprovidername = 'NeosDefaultProvider'");
    }
}
