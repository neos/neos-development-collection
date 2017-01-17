<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170110133114 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Adjust foreign key and index names to the renaming of TYPO3.Neos to Neos.Neos';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('ALTER INDEX idx_8e49a537694309e4 RENAME TO IDX_51265BE9694309E4');
        $this->addSql('ALTER INDEX flow_identity_typo3_neos_domain_model_domain RENAME TO flow_identity_neos_neos_domain_model_domain');
        $this->addSql('ALTER INDEX idx_1854b207b8872b4a RENAME TO IDX_9B02A4EB8872B4A');
        $this->addSql('ALTER INDEX idx_1854b2075ceb2c15 RENAME TO IDX_9B02A4E5CEB2C15');
        $this->addSql('ALTER INDEX flow_identity_typo3_neos_domain_model_site RENAME TO flow_identity_neos_neos_domain_model_site');
        $this->addSql('ALTER INDEX uniq_fc846daae931a6f5 RENAME TO UNIQ_ED60F5E3E931A6F5');
        $this->addSql('ALTER INDEX idx_30ab3a75b684c08 RENAME TO IDX_D6DBC30A5B684C08');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema)
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('ALTER INDEX uniq_ed60f5e3e931a6f5 RENAME TO uniq_fc846daae931a6f5');
        $this->addSql('ALTER INDEX idx_51265be9694309e4 RENAME TO idx_8e49a537694309e4');
        $this->addSql('ALTER INDEX flow_identity_neos_neos_domain_model_domain RENAME TO flow_identity_typo3_neos_domain_model_domain');
        $this->addSql('ALTER INDEX flow_identity_neos_neos_domain_model_site RENAME TO flow_identity_typo3_neos_domain_model_site');
        $this->addSql('ALTER INDEX idx_9b02a4e5ceb2c15 RENAME TO idx_1854b2075ceb2c15');
        $this->addSql('ALTER INDEX idx_9b02a4eb8872b4a RENAME TO idx_1854b207b8872b4a');
        $this->addSql('ALTER INDEX idx_d6dbc30a5b684c08 RENAME TO idx_30ab3a75b684c08');
    }
}
