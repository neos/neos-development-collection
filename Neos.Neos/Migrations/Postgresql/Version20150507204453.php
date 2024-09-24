<?php

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Add asset collection to site
 */
class Version20150507204453 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform));

        $this->addSql("ALTER TABLE typo3_neos_domain_model_site ADD assetcollection VARCHAR(40) DEFAULT NULL");
        $this->addSql("ALTER TABLE typo3_neos_domain_model_site ADD CONSTRAINT FK_1854B2075CEB2C15 FOREIGN KEY (assetcollection) REFERENCES typo3_media_domain_model_assetcollection (persistence_object_identifier) NOT DEFERRABLE INITIALLY IMMEDIATE");
        $this->addSql("CREATE INDEX IDX_1854B2075CEB2C15 ON typo3_neos_domain_model_site (assetcollection)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->abortIf(!($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform));

        $this->addSql("ALTER TABLE typo3_neos_domain_model_site DROP CONSTRAINT FK_1854B2075CEB2C15");
        $this->addSql("DROP INDEX IDX_1854B2075CEB2C15");
        $this->addSql("ALTER TABLE typo3_neos_domain_model_site DROP assetcollection");
    }
}
