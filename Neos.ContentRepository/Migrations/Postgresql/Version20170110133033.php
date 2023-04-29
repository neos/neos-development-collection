<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170110133033 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription(): string 
    {
        return 'Adjust foreign key and index names to the renaming of TYPO3.TYPO3CR to Neos.ContentRepository';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata ALTER dimensionvalues TYPE jsonb');
        $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata ALTER dimensionvalues DROP DEFAULT');
        $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata ALTER accessroles TYPE jsonb');
        $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata ALTER accessroles DROP DEFAULT');
        $this->addSql('ALTER INDEX idx_60a956b98d940019 RENAME TO IDX_CE6515698D940019');
        $this->addSql('ALTER INDEX idx_60a956b94930c33c RENAME TO IDX_CE6515694930C33C');
        $this->addSql('ALTER INDEX idx_60a956b92d45fe4d RENAME TO IDX_CE6515692D45FE4D');
        $this->addSql('ALTER INDEX uniq_60a956b92dbec7578d94001992f8fb01 RENAME TO UNIQ_CE6515692DBEC7578D94001992F8FB01');
        $this->addSql('ALTER INDEX uniq_60a956b9772e836a8d94001992f8fb012d45fe4d RENAME TO UNIQ_CE651569772E836A8D94001992F8FB012D45FE4D');
        $this->addSql('ALTER INDEX idx_71de9cfbe9bfe681 RENAME TO IDX_F7A3826CE9BFE681');
        $this->addSql('ALTER INDEX idx_71de9cfbbb46155 RENAME TO IDX_F7A3826CBB46155');
        $this->addSql('ALTER INDEX idx_6c144d3693bdc8e2 RENAME TO IDX_C4713BFF93BDC8E2');
        $this->addSql('ALTER INDEX uniq_6c144d3693bdc8e25e237e061d775834 RENAME TO UNIQ_C4713BFF93BDC8E25E237E061D775834');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata ALTER dimensionvalues TYPE JSON');
        $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata ALTER dimensionvalues DROP DEFAULT');
        $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata ALTER accessroles TYPE JSON');
        $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata ALTER accessroles DROP DEFAULT');
        $this->addSql('ALTER INDEX idx_ce6515692d45fe4d RENAME TO idx_60a956b92d45fe4d');
        $this->addSql('ALTER INDEX uniq_ce6515692dbec7578d94001992f8fb01 RENAME TO uniq_60a956b92dbec7578d94001992f8fb01');
        $this->addSql('ALTER INDEX idx_ce6515694930c33c RENAME TO idx_60a956b94930c33c');
        $this->addSql('ALTER INDEX idx_ce6515698d940019 RENAME TO idx_60a956b98d940019');
        $this->addSql('ALTER INDEX uniq_ce651569772e836a8d94001992f8fb012d45fe4d RENAME TO uniq_60a956b9772e836a8d94001992f8fb012d45fe4d');
        $this->addSql('ALTER INDEX idx_f7a3826ce9bfe681 RENAME TO idx_71de9cfbe9bfe681');
        $this->addSql('ALTER INDEX idx_f7a3826cbb46155 RENAME TO idx_71de9cfbbb46155');
        $this->addSql('ALTER INDEX idx_c4713bff93bdc8e2 RENAME TO idx_6c144d3693bdc8e2');
        $this->addSql('ALTER INDEX uniq_c4713bff93bdc8e25e237e061d775834 RENAME TO uniq_6c144d3693bdc8e25e237e061d775834');
    }
}
