<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260604102857 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform),
            'Migration can only be executed safely on "postgresql".'
        );

        $this->addSql('ALTER TABLE neos_media_domain_model_image ADD focalpointx INT DEFAULT NULL');
        $this->addSql('ALTER TABLE neos_media_domain_model_image ADD focalpointy INT DEFAULT NULL');
        $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant ADD focalpointx INT DEFAULT NULL');
        $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant ADD focalpointy INT DEFAULT NULL');
        $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail ADD focalpointx INT DEFAULT NULL');
        $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail ADD focalpointy INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf(
            !($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform),
            'Migration can only be executed safely on "postgresql".'
        );

        $this->addSql('ALTER TABLE neos_media_domain_model_image DROP focalpointx');
        $this->addSql('ALTER TABLE neos_media_domain_model_image DROP focalpointy');
        $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail DROP focalpointx');
        $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail DROP focalpointy');
        $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant DROP focalpointx');
        $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant DROP focalpointy');
    }
}
