<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\Shared;

use Neos\ContentRepositoryRegistry\Factory\EventStore\DoctrineEventStoreFactory;

/**
 * @internal CR upgrade internals
 */
trait EventStoreBackupTrait
{
    final protected function backupEventTable(): void
    {
        $backupEventTableName = DoctrineEventStoreFactory::databaseTableName($this->context->contentRepositoryId)
            . '_bkp_' . date('Y_m_d_H_i_s');
        $this->log(sprintf('Backup: copying events table to %s', $backupEventTableName));
        $this->copyEventTable($backupEventTableName);
    }

    final protected function copyEventTable(string $backupEventTableName): void
    {
        $this->context->dbal->executeStatement(
            'CREATE TABLE ' . $backupEventTableName . ' AS
            SELECT *
            FROM ' . $this->eventTableName
        );
    }
}
