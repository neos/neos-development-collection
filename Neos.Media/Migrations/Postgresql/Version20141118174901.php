<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * The migration Version20141118174900 wrongly creates ratiomode as INT, make it a VARCHAR
 */
class Version20141118174901 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_adjustment_abstractimageadjustment ALTER ratiomode TYPE VARCHAR(255)");
        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail ALTER ratiomode TYPE VARCHAR(255)");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_media_domain_model_adjustment_abstractimageadjustment ALTER ratiomode TYPE INT USING (ratiomode::integer)");
        $this->addSql("ALTER TABLE typo3_media_domain_model_thumbnail ALTER ratiomode TYPE INT USING (ratiomode::integer)");
    }
}
