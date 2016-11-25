<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Rename node names in neos_contentrepository_domain_model_nodedata
 */
class Version20161125125249 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET nodetype=REPLACE(nodetype, 'TYPO3.Neos:', 'Neos.Neos:')");

        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET properties=REPLACE(nodetype, 'TYPO3\\\\Media\\\\', 'Neos\\\\Media\\\\')");
        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET properties=REPLACE(nodetype, 'TYPO3\\\\Flow\\\\', 'Neos\\\\Flow\\\\')");
        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET properties=REPLACE(nodetype, 'TYPO3\\\\Neos\\\\', 'Neos\\\\Neos\\\\')");
        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET properties=REPLACE(nodetype, 'TYPO3\\\\Party\\\\', 'Neos\\\\Party\\\\')");
        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET properties=REPLACE(nodetype, 'TYPO3\\\\TYPO3CR\\\\', 'Neos\\\\ContentRepository\\\\')");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET nodetype=REPLACE(nodetype, 'Neos.Neos:', 'TYPO3.Neos:')");

        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET properties=REPLACE(nodetype, 'Neos\\\\Media\\\\', 'TYPO3\\\\Media\\\\')");
        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET properties=REPLACE(nodetype, 'Neos\\\\Flow\\\\', 'TYPO3\\\\Flow\\\\')");
        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET properties=REPLACE(nodetype, 'Neos\\\\Neos\\\\', 'TYPO3\\\\Neos\\\\')");
        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET properties=REPLACE(nodetype, 'Neos\\\\Party\\\\', 'TYPO3\\\\Party\\\\')");
        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET properties=REPLACE(nodetype, 'Neos\\\\ContentRepository\\\\', 'TYPO3\\\\TYPO3CR\\\\')");
    }
}
