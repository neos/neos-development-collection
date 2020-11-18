<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Platforms\MySQL57Platform;
use Doctrine\DBAL\Schema\Schema;

class Version20170110130113 extends AbstractMigration
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
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        // Renaming of indexes is only possible with MySQL version 5.7+
        if ($this->connection->getDatabasePlatform() instanceof MySQL57Platform) {
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata RENAME INDEX idx_60a956b98d940019 TO IDX_CE6515698D940019');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata RENAME INDEX idx_60a956b94930c33c TO IDX_CE6515694930C33C');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata RENAME INDEX idx_60a956b92d45fe4d TO IDX_CE6515692D45FE4D');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata RENAME INDEX uniq_60a956b92dbec7578d94001992f8fb01 TO UNIQ_CE6515692DBEC7578D94001992F8FB01');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata RENAME INDEX uniq_60a956b9772e836a8d94001992f8fb012d45fe4d TO UNIQ_CE651569772E836A8D94001992F8FB012D45FE4D');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_workspace RENAME INDEX idx_71de9cfbe9bfe681 TO IDX_F7A3826CE9BFE681');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_workspace RENAME INDEX idx_71de9cfbbb46155 TO IDX_F7A3826CBB46155');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedimension RENAME INDEX idx_6c144d3693bdc8e2 TO IDX_C4713BFF93BDC8E2');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedimension RENAME INDEX uniq_6c144d3693bdc8e25e237e061d775834 TO UNIQ_C4713BFF93BDC8E25E237E061D775834');
        } else {
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata DROP FOREIGN KEY FK_60A956B92D45FE4D');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata DROP FOREIGN KEY FK_60A956B98D940019');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata DROP FOREIGN KEY neos_contentrepository_domain_model_nodedata_ibfk_2');
            $this->addSql('DROP INDEX idx_60a956b98d940019 ON neos_contentrepository_domain_model_nodedata');
            $this->addSql('CREATE INDEX IDX_CE6515698D940019 ON neos_contentrepository_domain_model_nodedata (workspace)');
            $this->addSql('DROP INDEX idx_60a956b94930c33c ON neos_contentrepository_domain_model_nodedata');
            $this->addSql('CREATE INDEX IDX_CE6515694930C33C ON neos_contentrepository_domain_model_nodedata (contentobjectproxy)');
            $this->addSql('DROP INDEX idx_60a956b92d45fe4d ON neos_contentrepository_domain_model_nodedata');
            $this->addSql('CREATE INDEX IDX_CE6515692D45FE4D ON neos_contentrepository_domain_model_nodedata (movedto)');
            $this->addSql('DROP INDEX uniq_60a956b92dbec7578d94001992f8fb01 ON neos_contentrepository_domain_model_nodedata');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_CE6515692DBEC7578D94001992F8FB01 ON neos_contentrepository_domain_model_nodedata (pathhash, workspace, dimensionshash)');
            $this->addSql('DROP INDEX uniq_60a956b9772e836a8d94001992f8fb012d45fe4d ON neos_contentrepository_domain_model_nodedata');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_CE651569772E836A8D94001992F8FB012D45FE4D ON neos_contentrepository_domain_model_nodedata (identifier, workspace, dimensionshash, movedto)');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata ADD CONSTRAINT FK_60A956B92D45FE4D FOREIGN KEY (movedto) REFERENCES neos_contentrepository_domain_model_nodedata (persistence_object_identifier) ON DELETE SET NULL');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata ADD CONSTRAINT FK_60A956B98D940019 FOREIGN KEY (workspace) REFERENCES neos_contentrepository_domain_model_workspace (name) ON DELETE SET NULL');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata ADD CONSTRAINT neos_contentrepository_domain_model_nodedata_ibfk_2 FOREIGN KEY (contentobjectproxy) REFERENCES neos_contentrepository_domain_model_contentobjectproxy (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_workspace DROP FOREIGN KEY FK_71DE9CFBBB46155');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_workspace DROP FOREIGN KEY FK_71DE9CFBE9BFE681');
            $this->addSql('DROP INDEX idx_71de9cfbe9bfe681 ON neos_contentrepository_domain_model_workspace');
            $this->addSql('CREATE INDEX IDX_F7A3826CE9BFE681 ON neos_contentrepository_domain_model_workspace (baseworkspace)');
            $this->addSql('DROP INDEX idx_71de9cfbbb46155 ON neos_contentrepository_domain_model_workspace');
            $this->addSql('CREATE INDEX IDX_F7A3826CBB46155 ON neos_contentrepository_domain_model_workspace (rootnodedata)');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_workspace ADD CONSTRAINT FK_71DE9CFBBB46155 FOREIGN KEY (rootnodedata) REFERENCES neos_contentrepository_domain_model_nodedata (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_workspace ADD CONSTRAINT FK_71DE9CFBE9BFE681 FOREIGN KEY (baseworkspace) REFERENCES neos_contentrepository_domain_model_workspace (name) ON DELETE SET NULL');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedimension DROP FOREIGN KEY FK_6C144D3693BDC8E2');
            $this->addSql('DROP INDEX idx_6c144d3693bdc8e2 ON neos_contentrepository_domain_model_nodedimension');
            $this->addSql('CREATE INDEX IDX_C4713BFF93BDC8E2 ON neos_contentrepository_domain_model_nodedimension (nodedata)');
            $this->addSql('DROP INDEX uniq_6c144d3693bdc8e25e237e061d775834 ON neos_contentrepository_domain_model_nodedimension');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_C4713BFF93BDC8E25E237E061D775834 ON neos_contentrepository_domain_model_nodedimension (nodedata, name, value)');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedimension ADD CONSTRAINT FK_6C144D3693BDC8E2 FOREIGN KEY (nodedata) REFERENCES neos_contentrepository_domain_model_nodedata (persistence_object_identifier) ON DELETE CASCADE');
        }
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'mysql', 'Migration can only be executed safely on "mysql".');

        // Renaming of indexes is only possible with MySQL version 5.7+
        if ($this->connection->getDatabasePlatform() instanceof MySQL57Platform) {
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata RENAME INDEX uniq_ce6515692dbec7578d94001992f8fb01 TO UNIQ_60A956B92DBEC7578D94001992F8FB01');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata RENAME INDEX uniq_ce651569772e836a8d94001992f8fb012d45fe4d TO UNIQ_60A956B9772E836A8D94001992F8FB012D45FE4D');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata RENAME INDEX idx_ce6515692d45fe4d TO IDX_60A956B92D45FE4D');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata RENAME INDEX idx_ce6515698d940019 TO IDX_60A956B98D940019');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata RENAME INDEX idx_ce6515694930c33c TO IDX_60A956B94930C33C');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedimension RENAME INDEX uniq_c4713bff93bdc8e25e237e061d775834 TO UNIQ_6C144D3693BDC8E25E237E061D775834');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedimension RENAME INDEX idx_c4713bff93bdc8e2 TO IDX_6C144D3693BDC8E2');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_workspace RENAME INDEX idx_f7a3826ce9bfe681 TO IDX_71DE9CFBE9BFE681');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_workspace RENAME INDEX idx_f7a3826cbb46155 TO IDX_71DE9CFBBB46155');
        } else {
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata DROP FOREIGN KEY FK_CE6515698D940019');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata DROP FOREIGN KEY FK_CE6515694930C33C');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata DROP FOREIGN KEY FK_CE6515692D45FE4D');
            $this->addSql('DROP INDEX uniq_ce6515692dbec7578d94001992f8fb01 ON neos_contentrepository_domain_model_nodedata');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_60A956B92DBEC7578D94001992F8FB01 ON neos_contentrepository_domain_model_nodedata (pathhash, workspace, dimensionshash)');
            $this->addSql('DROP INDEX uniq_ce651569772e836a8d94001992f8fb012d45fe4d ON neos_contentrepository_domain_model_nodedata');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_60A956B9772E836A8D94001992F8FB012D45FE4D ON neos_contentrepository_domain_model_nodedata (identifier, workspace, dimensionshash, movedto)');
            $this->addSql('DROP INDEX idx_ce6515692d45fe4d ON neos_contentrepository_domain_model_nodedata');
            $this->addSql('CREATE INDEX IDX_60A956B92D45FE4D ON neos_contentrepository_domain_model_nodedata (movedto)');
            $this->addSql('DROP INDEX idx_ce6515698d940019 ON neos_contentrepository_domain_model_nodedata');
            $this->addSql('CREATE INDEX IDX_60A956B98D940019 ON neos_contentrepository_domain_model_nodedata (workspace)');
            $this->addSql('DROP INDEX idx_ce6515694930c33c ON neos_contentrepository_domain_model_nodedata');
            $this->addSql('CREATE INDEX IDX_60A956B94930C33C ON neos_contentrepository_domain_model_nodedata (contentobjectproxy)');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata ADD CONSTRAINT FK_CE6515698D940019 FOREIGN KEY (workspace) REFERENCES neos_contentrepository_domain_model_workspace (name) ON DELETE SET NULL');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata ADD CONSTRAINT FK_CE6515694930C33C FOREIGN KEY (contentobjectproxy) REFERENCES neos_contentrepository_domain_model_contentobjectproxy (persistence_object_identifier)');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedata ADD CONSTRAINT FK_CE6515692D45FE4D FOREIGN KEY (movedto) REFERENCES neos_contentrepository_domain_model_nodedata (persistence_object_identifier) ON DELETE SET NULL');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedimension DROP FOREIGN KEY FK_C4713BFF93BDC8E2');
            $this->addSql('DROP INDEX uniq_c4713bff93bdc8e25e237e061d775834 ON neos_contentrepository_domain_model_nodedimension');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_6C144D3693BDC8E25E237E061D775834 ON neos_contentrepository_domain_model_nodedimension (nodedata, name, value)');
            $this->addSql('DROP INDEX idx_c4713bff93bdc8e2 ON neos_contentrepository_domain_model_nodedimension');
            $this->addSql('CREATE INDEX IDX_6C144D3693BDC8E2 ON neos_contentrepository_domain_model_nodedimension (nodedata)');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_nodedimension ADD CONSTRAINT FK_C4713BFF93BDC8E2 FOREIGN KEY (nodedata) REFERENCES neos_contentrepository_domain_model_nodedata (persistence_object_identifier) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_workspace DROP FOREIGN KEY FK_F7A3826CE9BFE681');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_workspace DROP FOREIGN KEY FK_F7A3826CBB46155');
            $this->addSql('DROP INDEX idx_f7a3826ce9bfe681 ON neos_contentrepository_domain_model_workspace');
            $this->addSql('CREATE INDEX IDX_71DE9CFBE9BFE681 ON neos_contentrepository_domain_model_workspace (baseworkspace)');
            $this->addSql('DROP INDEX idx_f7a3826cbb46155 ON neos_contentrepository_domain_model_workspace');
            $this->addSql('CREATE INDEX IDX_71DE9CFBBB46155 ON neos_contentrepository_domain_model_workspace (rootnodedata)');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_workspace ADD CONSTRAINT FK_F7A3826CE9BFE681 FOREIGN KEY (baseworkspace) REFERENCES neos_contentrepository_domain_model_workspace (name) ON DELETE SET NULL');
            $this->addSql('ALTER TABLE neos_contentrepository_domain_model_workspace ADD CONSTRAINT FK_F7A3826CBB46155 FOREIGN KEY (rootnodedata) REFERENCES neos_contentrepository_domain_model_nodedata (persistence_object_identifier)');
        }
    }
}
