<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;
use Neos\Flow\Utility\Now;

/**
 * Add creation date, last modification date and last publication date to node data table
 */
class Version20150131174806 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $now = new Now();
        $default = $now->format($this->platform->getDateTimeFormatString());
        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ADD creationdatetime DATETIME NOT NULL, ADD lastmodificationdatetime DATETIME NOT NULL, ADD lastpublicationdatetime DATETIME DEFAULT NULL");
        $this->addSql("UPDATE typo3_typo3cr_domain_model_nodedata SET creationdatetime = '" . $default . "', lastmodificationdatetime = '" . $default . "'");
        $this->addSql("UPDATE typo3_typo3cr_domain_model_nodedata SET lastpublicationdatetime = '" . $default . "' WHERE workspace = 'live'");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata DROP creationdatetime, DROP lastmodificationdatetime, DROP lastpublicationdatetime");
    }
}
