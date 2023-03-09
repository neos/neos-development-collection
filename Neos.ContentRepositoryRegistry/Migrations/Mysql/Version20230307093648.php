<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drop obsolete tables
 */
final class Version20230307093648 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1027Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1027Platform'."
        );

        $this->addSql('DROP TABLE neos_contentrepository_events');
        $this->addSql('DROP TABLE neos_contentrepository_projection_change');
        $this->addSql('DROP TABLE neos_contentrepository_projection_contentstream_v1');
        $this->addSql('DROP TABLE neos_contentrepository_projection_nodehiddenstate');
        $this->addSql('DROP TABLE neos_contentrepository_projection_workspace_v1');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1027Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1027Platform'."
        );

        $this->addSql('CREATE TABLE neos_contentrepository_events (sequencenumber INT AUTO_INCREMENT NOT NULL, stream VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, version BIGINT UNSIGNED NOT NULL, type VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, payload LONGTEXT CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, metadata LONGTEXT CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, id VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, correlationidentifier VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, causationidentifier VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, recordedat DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_67A26AC9EE6C504 (correlationidentifier), UNIQUE INDEX id_uniq (id), UNIQUE INDEX stream_version_uniq (stream, version), PRIMARY KEY(sequencenumber)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE neos_contentrepository_projection_change (contentStreamIdentifier VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, changed TINYINT(1) NOT NULL, moved TINYINT(1) NOT NULL, nodeAggregateIdentifier VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, originDimensionSpacePoint TEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, originDimensionSpacePointHash VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, deleted TINYINT(1) NOT NULL, removalAttachmentPoint VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, PRIMARY KEY(contentStreamIdentifier, nodeAggregateIdentifier, originDimensionSpacePointHash)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE neos_contentrepository_projection_contentstream_v1 (contentStreamIdentifier VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, sourceContentStreamIdentifier VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, state VARCHAR(20) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, removed TINYINT(1) DEFAULT \'0\') DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE neos_contentrepository_projection_nodehiddenstate (contentstreamidentifier VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, nodeaggregateidentifier VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, dimensionspacepointhash VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, dimensionspacepoint TEXT CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, hidden TINYINT(1) DEFAULT NULL, PRIMARY KEY(contentstreamidentifier, nodeaggregateidentifier, dimensionspacepointhash)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE neos_contentrepository_projection_workspace_v1 (workspacename VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, baseworkspacename VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, workspacetitle VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, workspacedescription VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, workspaceowner VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, currentcontentstreamidentifier VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, status VARCHAR(50) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, PRIMARY KEY(workspacename)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
    }
}
