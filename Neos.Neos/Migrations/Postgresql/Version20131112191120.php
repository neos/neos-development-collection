<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Remove the site content object proxy from all site nodes and change their node type to "TYPO3.Neos:Shortcut"
 */
class Version20131112191120 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("UPDATE typo3_typo3cr_domain_model_nodedata SET contentobjectproxy = NULL, nodetype = 'TYPO3.Neos:Shortcut' WHERE parentpath = '/sites' AND nodetype = 'unstructured'");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("UPDATE typo3_typo3cr_domain_model_nodedata SET contentobjectproxy = (SELECT c.persistence_object_identifier FROM typo3_typo3cr_domain_model_contentobjectproxy AS c, typo3_neos_domain_model_site AS s WHERE c.targetid = s.persistence_object_identifier AND c.targettype = 'TYPO3\\Neos\\Domain\\Model\\Site' AND s.nodename = SUBSTRING(typo3_typo3cr_domain_model_nodedata.path, 8)), nodetype = 'unstructured' WHERE contentobjectproxy IS NULL AND parentpath = '/sites' AND nodetype = 'TYPO3.Neos:Shortcut'");
    }
}
