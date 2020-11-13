<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Enforce lowercase node paths.
 */
class Version20150408112832 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("UPDATE typo3_typo3cr_domain_model_nodedata SET path = LOWER(path), parentpath = LOWER(parentpath), pathhash = MD5(LOWER(path)), parentpathhash = MD5(LOWER(parentpath))");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        // No down migration available
    }
}
