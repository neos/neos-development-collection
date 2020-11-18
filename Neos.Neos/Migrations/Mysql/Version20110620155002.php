<?php
namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Database structure as per the T3CON11 CfP launch on 2011-05-20
 */
class Version20110620155002 extends AbstractMigration
{
    /**
     * @param Schema $schema
     * @return void
     */
    public function up(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("CREATE TABLE typo3_domain (flow3_persistence_identifier VARCHAR(40) NOT NULL, typo3_site VARCHAR(40) DEFAULT NULL, hostpattern VARCHAR(255) DEFAULT NULL, INDEX IDX_64D1A917E12C6E67 (typo3_site), PRIMARY KEY(flow3_persistence_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE typo3_site (flow3_persistence_identifier VARCHAR(40) NOT NULL, name VARCHAR(255) DEFAULT NULL, nodename VARCHAR(255) DEFAULT NULL, state INT DEFAULT NULL, siteresourcespackagekey VARCHAR(255) DEFAULT NULL, PRIMARY KEY(flow3_persistence_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE typo3_user (flow3_persistence_identifier VARCHAR(40) NOT NULL, typo3_userpreferences VARCHAR(40) DEFAULT NULL, UNIQUE INDEX UNIQ_5FCB1CAF3210CEC (typo3_userpreferences), PRIMARY KEY(flow3_persistence_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->addSql("CREATE TABLE typo3_userpreferences (flow3_persistence_identifier VARCHAR(40) NOT NULL, preferences LONGTEXT DEFAULT NULL COMMENT '(DC2Type:array)', PRIMARY KEY(flow3_persistence_identifier)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB");
        $this->addSql("ALTER TABLE typo3_domain ADD CONSTRAINT typo3_domain_ibfk_1 FOREIGN KEY (typo3_site) REFERENCES typo3_site(flow3_persistence_identifier)");
        $this->addSql("ALTER TABLE typo3_user ADD CONSTRAINT typo3_user_ibfk_1 FOREIGN KEY (typo3_userpreferences) REFERENCES typo3_userpreferences(flow3_persistence_identifier)");
        $this->addSql("ALTER TABLE typo3_user ADD CONSTRAINT typo3_user_ibfk_2 FOREIGN KEY (flow3_persistence_identifier) REFERENCES party_abstractparty(flow3_persistence_identifier) ON DELETE CASCADE");
    }

    /**
     * @param Schema $schema
     * @return void
     */
    public function down(Schema $schema): void 
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() != "mysql");

        $this->addSql("ALTER TABLE typo3_user DROP FOREIGN KEY typo3_user_ibfk_2");
        $this->addSql("ALTER TABLE typo3_user DROP FOREIGN KEY typo3_user_ibfk_1");
        $this->addSql("ALTER TABLE typo3_domain DROP FOREIGN KEY typo3_domain_ibfk_1");
        $this->addSql("DROP TABLE typo3_domain");
        $this->addSql("DROP TABLE typo3_site");
        $this->addSql("DROP TABLE typo3_user");
        $this->addSql("DROP TABLE typo3_userpreferences");
    }
}
