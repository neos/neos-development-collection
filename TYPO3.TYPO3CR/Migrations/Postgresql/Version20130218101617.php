<?php
namespace TYPO3\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Corrects occurrences of "TYPO3\Neos\Domain\Model\Site" in TYPO3\TYPO3CR\Domain\Model\ContentObjectProxy's targettype property
 */
class Version20130218101617 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("UPDATE typo3_typo3cr_domain_model_contentobjectproxy SET targettype = 'TYPO3\\\\Neos\\\\Domain\\\\Model\\\\Site' WHERE targettype = 'TYPO3\\\\TYPO3\\\\Domain\\\\Model\\\\Site'");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("UPDATE typo3_typo3cr_domain_model_contentobjectproxy SET targettype = 'TYPO3\\\\TYPO3\\\\Domain\\\\Model\\\\Site' WHERE targettype = 'TYPO3\\\\Neos\\\\Domain\\\\Model\\\\Site'");
    }
}
