<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240604184831 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant ADD focalpointx INT DEFAULT NULL, ADD focalpointy INT DEFAULT NULL');
        $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail ADD focalpointx INT DEFAULT NULL, ADD focalpointy INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on "mysql".');

        $this->addSql('ALTER TABLE neos_media_domain_model_imagevariant DROP focalpointx, DROP focalpointy');
        $this->addSql('ALTER TABLE neos_media_domain_model_thumbnail DROP focalpointx, DROP focalpointy');

    }
}
