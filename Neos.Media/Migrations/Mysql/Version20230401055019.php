<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230401055019 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the parent property to the AssetCollection entity.';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection ADD parent VARCHAR(40) DEFAULT NULL');
        $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection ADD CONSTRAINT FK_74C770A3D8E604F FOREIGN KEY (parent) REFERENCES neos_media_domain_model_assetcollection (persistence_object_identifier) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_74C770A3D8E604F ON neos_media_domain_model_assetcollection (parent)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(!$this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection DROP FOREIGN KEY FK_74C770A3D8E604F');
        $this->addSql('DROP INDEX IDX_74C770A3D8E604F ON neos_media_domain_model_assetcollection');
        $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection DROP parent');
    }
}
