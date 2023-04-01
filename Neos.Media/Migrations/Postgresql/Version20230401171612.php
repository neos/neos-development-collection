<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20230401171612 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adds the parent property to AssetCollections';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection ADD parent VARCHAR(40) DEFAULT NULL');
        $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection ADD CONSTRAINT FK_74C770A3D8E604F FOREIGN KEY (parent) REFERENCES neos_media_domain_model_assetcollection (persistence_object_identifier) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_74C770A3D8E604F ON neos_media_domain_model_assetcollection (parent)');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on "postgresql".');

        $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection DROP CONSTRAINT FK_74C770A3D8E604F');
        $this->addSql('DROP INDEX IDX_74C770A3D8E604F');
        $this->addSql('ALTER TABLE neos_media_domain_model_assetcollection DROP parent');
    }
}
