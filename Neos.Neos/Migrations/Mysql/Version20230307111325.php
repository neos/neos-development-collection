<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Drop obsolete tables
 */
final class Version20230307111325 extends AbstractMigration
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

        $this->addSql('DROP TABLE neos_neos_projection_document_uri');
        $this->addSql('DROP TABLE neos_neos_projection_document_uri_livecontentstreams');
        $this->addSql('DROP TABLE neos_neos_projection_domain_v1');
        $this->addSql('DROP TABLE neos_neos_projection_site_v1');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf(
            !$this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\MariaDb1027Platform,
            "Migration can only be executed safely on '\Doctrine\DBAL\Platforms\MariaDb1027Platform'."
        );

        $this->addSql('CREATE TABLE neos_neos_projection_document_uri (nodeaggregateidentifier VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'\' NOT NULL COLLATE `utf8mb4_general_ci`, uripath VARCHAR(4000) CHARACTER SET utf8mb4 DEFAULT \'\' NOT NULL COLLATE `utf8mb4_general_ci`, dimensionspacepointhash VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'\' NOT NULL COLLATE `utf8mb4_general_ci`, disabled TINYINT(1) DEFAULT \'0\' NOT NULL, nodeaggregateidentifierpath VARCHAR(4000) CHARACTER SET utf8mb4 DEFAULT \'\' NOT NULL COLLATE `utf8mb4_general_ci`, sitenodename VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'\' NOT NULL COLLATE `utf8mb4_general_ci`, origindimensionspacepointhash VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'\' NOT NULL COLLATE `utf8mb4_general_ci`, parentnodeaggregateidentifier VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, precedingnodeaggregateidentifier VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, succeedingnodeaggregateidentifier VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, shortcuttarget VARCHAR(1000) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, UNIQUE INDEX variant (nodeaggregateidentifier, dimensionspacepointhash), INDEX preceding_succeeding (parentnodeaggregateidentifier, precedingnodeaggregateidentifier, succeedingnodeaggregateidentifier), INDEX sitenode_uripath (sitenodename, uripath(100))) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE neos_neos_projection_document_uri_livecontentstreams (contentstreamidentifier VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'\' NOT NULL COLLATE `utf8mb4_general_ci`, workspacename VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT \'\' NOT NULL COLLATE `utf8mb4_general_ci`, PRIMARY KEY(contentstreamidentifier)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE neos_neos_projection_domain_v1 (persistence_object_identifier VARCHAR(40) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, site VARCHAR(40) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, hostname VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, active TINYINT(1) NOT NULL, urischeme VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, domainport INT DEFAULT NULL, INDEX IDX_7A69A7D9694309E4 (site), PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('CREATE TABLE neos_neos_projection_site_v1 (persistence_object_identifier VARCHAR(40) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, name VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, nodename VARCHAR(255) CHARACTER SET utf8 NOT NULL COLLATE `utf8_unicode_ci`, active TINYINT(1) NOT NULL, siteresourcespackagekey VARCHAR(255) CHARACTER SET utf8 DEFAULT NULL COLLATE `utf8_unicode_ci`, PRIMARY KEY(persistence_object_identifier)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE neos_neos_projection_domain_v1 ADD CONSTRAINT FK_7A69A7D9694309E4 FOREIGN KEY (site) REFERENCES neos_neos_projection_site_v1 (persistence_object_identifier)');
    }
}
