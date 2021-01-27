<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210126083531 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'add index to dimensions hash';
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Exception
     */
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE INDEX dimensionshash ON neos_neos_eventlog_domain_model_event (dimensionshash)');
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Exception
     */
    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX dimensionshash ON neos_neos_eventlog_domain_model_event');
    }
}
