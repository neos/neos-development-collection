<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Adjust key on Tag domain model to ON DELETE SET NULL
 */
final class Version20230430183157 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Adjust key on Tag domain model to ON DELETE SET NULL';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MysqlPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MysqlPlatform'."
        );

        $this->addSql('ALTER TABLE neos_media_domain_model_tag DROP FOREIGN KEY FK_CA4889693D8E604F');
        $this->addSql('ALTER TABLE neos_media_domain_model_tag ADD CONSTRAINT FK_CA4889693D8E604F FOREIGN KEY (parent) REFERENCES neos_media_domain_model_tag (persistence_object_identifier) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MysqlPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MysqlPlatform'."
        );

        $this->addSql('ALTER TABLE neos_media_domain_model_tag DROP FOREIGN KEY FK_CA4889693D8E604F');
        $this->addSql('ALTER TABLE neos_media_domain_model_tag ADD CONSTRAINT FK_CA4889693D8E604F FOREIGN KEY (parent) REFERENCES neos_media_domain_model_tag (persistence_object_identifier)');
    }
}
