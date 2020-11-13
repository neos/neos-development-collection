<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Add asset collection to site
 */
class Version20150507204452 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_neos_domain_model_site ADD assetcollection VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_neos_domain_model_site ADD CONSTRAINT FK_1854B2075CEB2C15 FOREIGN KEY (assetcollection) REFERENCES typo3_media_domain_model_assetcollection (persistence_object_identifier)");
        $this->addSql("CREATE INDEX IDX_1854B2075CEB2C15 ON typo3_neos_domain_model_site (assetcollection)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_neos_domain_model_site DROP FOREIGN KEY FK_1854B2075CEB2C15");
        $this->addSql("DROP INDEX IDX_1854B2075CEB2C15 ON typo3_neos_domain_model_site");
        $this->addSql("ALTER TABLE typo3_neos_domain_model_site DROP assetcollection");
    }
}
