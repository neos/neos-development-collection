<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Corrects occurrences of "Neos\Neos\Domain\Model\Site" in Neos\ContentRepository\Domain\Model\ContentObjectProxy's targettype property
 */
class Version20130218100324 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("UPDATE typo3_typo3cr_domain_model_contentobjectproxy SET targettype = 'TYPO3\\\\Neos\\\\Domain\\\\Model\\\\Site' WHERE targettype = 'TYPO3\\\\TYPO3\\\\Domain\\\\Model\\\\Site'");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("UPDATE typo3_typo3cr_domain_model_contentobjectproxy SET targettype = 'TYPO3\\\\TYPO3\\\\Domain\\\\Model\\\\Site' WHERE targettype = 'TYPO3\\\\Neos\\\\Domain\\\\Model\\\\Site'");
    }
}
