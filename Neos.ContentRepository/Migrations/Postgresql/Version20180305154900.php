<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Fix uniqe index for nodedata(identifier, workspace, dimensionshash, ismoved)
 */
class Version20180305154900 extends AbstractMigration
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
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('DROP INDEX uniq_ce651569772e836a8d94001992f8fb012d45fe4d');
        $this->addSql('DROP INDEX idx_ce6515692d45fe4d');
        $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata ADD ismoved BOOLEAN NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CE6515692D45FE4D ON neos_contentrepository_domain_model_nodedata (movedto)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_CE651569772E836A8D94001992F8FB01E3AD3CF ON neos_contentrepository_domain_model_nodedata (identifier, workspace, dimensionshash, ismoved)');
    }

    /**
     * @param Schema $schema
     * @return void
     * @throws \Doctrine\DBAL\Migrations\AbortMigrationException
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('DROP INDEX UNIQ_CE6515692D45FE4D');
        $this->addSql('DROP INDEX UNIQ_CE651569772E836A8D94001992F8FB01E3AD3CF');
        $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata DROP ismoved');
        $this->addSql('CREATE UNIQUE INDEX uniq_ce651569772e836a8d94001992f8fb012d45fe4d ON neos_contentrepository_domain_model_nodedata (identifier, workspace, dimensionshash, movedto)');
        $this->addSql('CREATE INDEX idx_ce6515692d45fe4d ON neos_contentrepository_domain_model_nodedata (movedto)');
    }
}
