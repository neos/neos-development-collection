<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260604093117 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MysqlPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MysqlPlatform'."
        );


        $this->addSql('ALTER TABLE neos_media_domain_model_image ADD focalpointx INT DEFAULT NULL, ADD focalpointy INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MysqlPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MysqlPlatform'."
        );

        $this->addSql('ALTER TABLE neos_media_domain_model_image DROP focalpointx, DROP focalpointy');
    }
}
