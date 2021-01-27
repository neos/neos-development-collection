<?php
declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Neos\ContentRepository\Utility;

final class Version20210125134503 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'add dimensions hash to node event model';
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Exception
     */
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE neos_neos_eventlog_domain_model_event ADD dimensionshash VARCHAR(32) DEFAULT NULL');
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Exception
     */
    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE neos_neos_eventlog_domain_model_event DROP dimensionshash');
    }

    /**
     * @param Schema $schema
     * @throws \Doctrine\DBAL\Driver\Exception
     * @throws \Doctrine\DBAL\Exception
     */
    public function postUp(Schema $schema): void
    {
        $eventLogResult = $this->connection->executeQuery('SELECT uid, dimension FROM neos_neos_eventlog_domain_model_event');

        while ($eventLogInfo = $eventLogResult->fetchAssociative()) {
            if ($eventLogInfo['dimension'] !== null) {
                $dimensionsArray = unserialize($eventLogInfo['dimension'], ['allowed_classes' => false]);
                $dimensionsHash = Utility::sortDimensionValueArrayAndReturnDimensionsHash($dimensionsArray);
                $this->connection->executeStatement('UPDATE neos_neos_eventlog_domain_model_event SET dimensionshash = ? WHERE uid = ?', [$dimensionsHash, $eventLogInfo['uid']]);
            }
        }
    }
}
