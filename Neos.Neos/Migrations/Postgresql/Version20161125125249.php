<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
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
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET nodetype=REPLACE(nodetype, 'TYPO3.Neos:', 'Neos.Neos:')");

        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET properties=CAST(REPLACE(CAST(properties AS TEXT), 'TYPO3\\\\Media\\\\', 'Neos\\\\Media\\\\') AS jsonb)");
        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET properties=CAST(REPLACE(CAST(properties AS TEXT), 'TYPO3\\\\Flow\\\\', 'Neos\\\\Flow\\\\') AS jsonb)");
        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET properties=CAST(REPLACE(CAST(properties AS TEXT), 'TYPO3\\\\Neos\\\\', 'Neos\\\\Neos\\\\') AS jsonb)");
        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET properties=CAST(REPLACE(CAST(properties AS TEXT), 'TYPO3\\\\Party\\\\', 'Neos\\\\Party\\\\') AS jsonb)");
        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET properties=CAST(REPLACE(CAST(properties AS TEXT), 'TYPO3\\\\TYPO3CR\\\\', 'Neos\\\\ContentRepository\\\\') AS jsonb)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET nodetype=REPLACE(nodetype, 'Neos.Neos:', 'TYPO3.Neos:')");

        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET properties=CAST(REPLACE(CAST(properties AS TEXT), 'Neos\\\\Media\\\\', 'TYPO3\\\\Media\\\\') AS jsonb)");
        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET properties=CAST(REPLACE(CAST(properties AS TEXT), 'Neos\\\\Flow\\\\', 'TYPO3\\\\Flow\\\\') AS jsonb)");
        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET properties=CAST(REPLACE(CAST(properties AS TEXT), 'Neos\\\\Neos\\\\', 'TYPO3\\\\Neos\\\\') AS jsonb)");
        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET properties=CAST(REPLACE(CAST(properties AS TEXT), 'Neos\\\\Party\\\\', 'TYPO3\\\\Party\\\\') AS jsonb)");
        $this->addSql("UPDATE neos_contentrepository_domain_model_nodedata SET properties=CAST(REPLACE(CAST(properties AS TEXT), 'Neos\\\\ContentRepository\\\\', 'TYPO3\\\\TYPO3CR\\\\') AS jsonb)");
    }
}
