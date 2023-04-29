<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Rename "hiddenBeforeDate" to "hiddenBeforeDateTime"; same for hiddenAfter* for consistency
 */
class Version20111215172027 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql('ALTER TABLE typo3_typo3cr_domain_model_node CHANGE hiddenbeforedate hiddenbeforedatetime DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE typo3_typo3cr_domain_model_node CHANGE hiddenafterdate hiddenafterdatetime DATETIME DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql('ALTER TABLE typo3_typo3cr_domain_model_node CHANGE hiddenbeforedatetime hiddenbeforedate DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE typo3_typo3cr_domain_model_node CHANGE hiddenafterdatetime hiddenafterdate DATETIME DEFAULT NULL');
    }
}
