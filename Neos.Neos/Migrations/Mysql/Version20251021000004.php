<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251021000004 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create tables for Neos.Neos';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MySQLPlatform'."
        );
        $this->addSql('CREATE TABLE neos_neos_domain_model_domain (persistence_object_identifier VARCHAR(40) NOT NULL, site VARCHAR(40) DEFAULT NULL, hostname VARCHAR(255) NOT NULL, scheme VARCHAR(255) DEFAULT NULL, port INT DEFAULT NULL, active TINYINT(1) NOT NULL, INDEX IDX_51265BE9694309E4 (site), UNIQUE INDEX flow_identity_neos_neos_domain_model_domain (hostname), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neos_neos_domain_model_site (persistence_object_identifier VARCHAR(40) NOT NULL, primarydomain VARCHAR(40) DEFAULT NULL, assetcollection VARCHAR(40) DEFAULT NULL, name VARCHAR(255) NOT NULL, nodename VARCHAR(255) NOT NULL, state INT NOT NULL, siteresourcespackagekey VARCHAR(255) NOT NULL, INDEX IDX_9B02A4EB8872B4A (primarydomain), INDEX IDX_9B02A4E5CEB2C15 (assetcollection), UNIQUE INDEX flow_identity_neos_neos_domain_model_site (nodename), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neos_neos_domain_model_user (persistence_object_identifier VARCHAR(40) NOT NULL, preferences VARCHAR(40) DEFAULT NULL, UNIQUE INDEX UNIQ_ED60F5E3E931A6F5 (preferences), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE neos_neos_domain_model_userpreferences (persistence_object_identifier VARCHAR(40) NOT NULL, preferences LONGTEXT NOT NULL COMMENT \'(DC2Type:array)\', PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE neos_neos_domain_model_domain ADD CONSTRAINT FK_51265BE9694309E4 FOREIGN KEY (site) REFERENCES neos_neos_domain_model_site (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neos_neos_domain_model_site ADD CONSTRAINT FK_9B02A4EB8872B4A FOREIGN KEY (primarydomain) REFERENCES neos_neos_domain_model_domain (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neos_neos_domain_model_site ADD CONSTRAINT FK_9B02A4E5CEB2C15 FOREIGN KEY (assetcollection) REFERENCES neos_media_domain_model_assetcollection (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neos_neos_domain_model_user ADD CONSTRAINT FK_ED60F5E3E931A6F5 FOREIGN KEY (preferences) REFERENCES neos_neos_domain_model_userpreferences (persistence_object_identifier)');
        $this->addSql('ALTER TABLE neos_neos_domain_model_user ADD CONSTRAINT FK_ED60F5E347A46B0A FOREIGN KEY (persistence_object_identifier) REFERENCES neos_party_domain_model_abstractparty (persistence_object_identifier) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MySQLPlatform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MySQLPlatform'."
        );

        $this->addSql('ALTER TABLE neos_neos_domain_model_domain DROP FOREIGN KEY FK_51265BE9694309E4');
        $this->addSql('ALTER TABLE neos_neos_domain_model_site DROP FOREIGN KEY FK_9B02A4EB8872B4A');
        $this->addSql('ALTER TABLE neos_neos_domain_model_site DROP FOREIGN KEY FK_9B02A4E5CEB2C15');
        $this->addSql('ALTER TABLE neos_neos_domain_model_user DROP FOREIGN KEY FK_ED60F5E3E931A6F5');
        $this->addSql('ALTER TABLE neos_neos_domain_model_user DROP FOREIGN KEY FK_ED60F5E347A46B0A');
        $this->addSql('DROP TABLE neos_neos_domain_model_domain');
        $this->addSql('DROP TABLE neos_neos_domain_model_site');
        $this->addSql('DROP TABLE neos_neos_domain_model_user');
        $this->addSql('DROP TABLE neos_neos_domain_model_userpreferences');
    }
}
