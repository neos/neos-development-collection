<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

class Version20170110133136 extends AbstractMigration
{

    /**
     * @return string
     */
    public function getDescription(): string 
    {
        return 'Adjust foreign key and index names to the renaming of TYPO3.Media to Neos.Media';
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('ALTER INDEX idx_84416fdca76d06e6 RENAME TO IDX_8B2F26F8A76D06E6');
        $this->addSql('ALTER INDEX uniq_b8306b8ebc91f416 RENAME TO UNIQ_675F9550BC91F416');
        $this->addSql('ALTER INDEX idx_daf7a1eb1db69eed RENAME TO IDX_915BC7A21DB69EED');
        $this->addSql('ALTER INDEX idx_daf7a1eb48d8c57e RENAME TO IDX_915BC7A248D8C57E');
        $this->addSql('ALTER INDEX idx_e90d72512a965871 RENAME TO IDX_1305D4CE2A965871');
        $this->addSql('ALTER INDEX idx_e90d72511db69eed RENAME TO IDX_1305D4CE1DB69EED');
        $this->addSql('ALTER INDEX idx_a41705672a965871 RENAME TO IDX_522F02632A965871');
        $this->addSql('ALTER INDEX idx_a417056748d8c57e RENAME TO IDX_522F026348D8C57E');
        $this->addSql('ALTER INDEX idx_758edebd55ff4171 RENAME TO IDX_C4BF979F55FF4171');
        $this->addSql('ALTER INDEX idx_b7ce141455ff4171 RENAME TO IDX_3A163C4955FF4171');
        $this->addSql('ALTER INDEX uniq_b7ce1414bc91f416 RENAME TO UNIQ_3A163C49BC91F416');
        $this->addSql('ALTER INDEX originalasset_configurationhash RENAME TO UNIQ_3A163C4955FF41717F7CBA1A');
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('ALTER INDEX uniq_675f9550bc91f416 RENAME TO uniq_b8306b8ebc91f416');
        $this->addSql('ALTER INDEX idx_915bc7a21db69eed RENAME TO idx_daf7a1eb1db69eed');
        $this->addSql('ALTER INDEX idx_915bc7a248d8c57e RENAME TO idx_daf7a1eb48d8c57e');
        $this->addSql('ALTER INDEX idx_8b2f26f8a76d06e6 RENAME TO idx_84416fdca76d06e6');
        $this->addSql('ALTER INDEX idx_1305d4ce2a965871 RENAME TO idx_e90d72512a965871');
        $this->addSql('ALTER INDEX idx_1305d4ce1db69eed RENAME TO idx_e90d72511db69eed');
        $this->addSql('ALTER INDEX idx_522f02632a965871 RENAME TO idx_a41705672a965871');
        $this->addSql('ALTER INDEX idx_522f026348d8c57e RENAME TO idx_a417056748d8c57e');
        $this->addSql('ALTER INDEX idx_c4bf979f55ff4171 RENAME TO idx_758edebd55ff4171');
        $this->addSql('ALTER INDEX uniq_3a163c4955ff41717f7cba1a RENAME TO originalasset_configurationhash');
        $this->addSql('ALTER INDEX uniq_3a163c49bc91f416 RENAME TO uniq_b7ce1414bc91f416');
        $this->addSql('ALTER INDEX idx_3a163c4955ff4171 RENAME TO idx_b7ce141455ff4171');
    }
}
