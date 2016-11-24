<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Change all existing serialized ImageVariants to arrays to be picked up and converted by the matching TYPO3CR migration.
 */
class Version20141017174559 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        $this->addSql("UPDATE typo3_typo3cr_domain_model_nodedata SET properties = REPLACE(properties, 'O:37:\"TYPO3\\\\Media\\\\Domain\\\\Model\\\\ImageVariant\"', 'a');");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");
        $this->write('The conversion of nodes to the new resource management cannot be reverted with a database migration.');
    }
}
