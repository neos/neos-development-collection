<?php

declare(strict_types=1);

namespace Neos\Flow\Persistence\Doctrine\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Neos\ContentRepositoryRegistry\DoctrineMigration\ProjectionDoctrineMigrationHelper;

final class Version20230318200512 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'rename projection table columns';
    }

    public function up(Schema $schema): void
    {
        foreach (ProjectionDoctrineMigrationHelper::configuredContentRepositoryIds() as $contentRepositoryId) {
            $tableName = sprintf('cr_%s_p_neos_change', $contentRepositoryId->value);
            if ($schema->hasTable($tableName)) {
                $tableSchema = $schema->getTable($tableName);
                if ($tableSchema->hasColumn('nodeAggregateIdentifier')) {
                    // table in old format -> we migrate to new.
                    $this->addSql(sprintf('ALTER TABLE %s RENAME COLUMN nodeAggregateIdentifier TO nodeAggregateId; ', $tableName));
                    $this->addSql(sprintf('ALTER TABLE %s RENAME COLUMN contentStreamIdentifier TO contentStreamId; ', $tableName));
                } else {
                    // table already directly created in new format -> we do not need to do anything.
                }
            }
        }
    }

    public function down(Schema $schema): void
    {
    }
}
