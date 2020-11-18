<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Adjust properties column
 */
class Version20130730151320 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER properties TYPE bytea USING DECODE(properties, 'escape')");
        $this->addSql("COMMENT ON COLUMN typo3_typo3cr_domain_model_nodedata.properties IS '(DC2Type:objectarray)'");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "postgresql");

        $this->addSql("ALTER TABLE typo3_typo3cr_domain_model_nodedata ALTER properties TYPE text USING ENCODE(properties, 'escape')");
        $this->addSql("COMMENT ON COLUMN typo3_typo3cr_domain_model_nodedata.properties IS '(DC2Type:array)'");
    }
}
