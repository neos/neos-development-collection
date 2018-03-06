<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Fix uniqe index for nodedata(identifier, workspace, dimensionshash, ismoved)
 */
class Version20180305172923 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Fix uniqe index for nodedata(identifier, workspace, dimensionshash, ismoved)';
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata DROP INDEX IDX_CE6515692D45FE4D, ADD UNIQUE INDEX UNIQ_CE6515692D45FE4D (movedto)');
        $this->addSql('DROP INDEX UNIQ_CE651569772E836A8D94001992F8FB012D45FE4D ON neos_contentrepository_domain_model_nodedata');
        $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata ADD ismoved TINYINT(1) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CE651569772E836A8D94001992F8FB01E3AD3CF ON neos_contentrepository_domain_model_nodedata (identifier, workspace, dimensionshash, ismoved)');
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata DROP INDEX UNIQ_CE6515692D45FE4D, ADD INDEX IDX_CE6515692D45FE4D (movedto)');
        $this->addSql('DROP INDEX UNIQ_CE651569772E836A8D94001992F8FB01E3AD3CF ON neos_contentrepository_domain_model_nodedata');
        $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata DROP ismoved');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CE651569772E836A8D94001992F8FB012D45FE4D ON neos_contentrepository_domain_model_nodedata (identifier, workspace, dimensionshash, movedto)');
    }
}
