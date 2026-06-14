<?php
declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Command;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Service\EventMigrationServiceFactory;
use Neos\Flow\Cli\CommandController;

final class MigrateEventsCommandController extends CommandController
{

    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        private readonly EventMigrationServiceFactory $eventMigrationServiceFactory,
    ) {
        parent::__construct();
    }

    /**
     * Optional migration to adjust event time stamps and node dates to UTC
     *
     * https://github.com/neos/neos-development-collection/pull/5716
     *
     * By storing "recordedAt" as datetime field we lost its original timezone information.
     * But we can make the assumption that its timezone should be the same as the one encoded in the ATOM metadata field "initiatingTimeStamp"
     *
     * The migration first groups all events by the ATOM offset found in "initiatingTimeStamp".
     * If all events are UTC "+00:00" the migration is not necessary. For all non UTC groups we convert the "recordedAt" datetime field
     * to the datetime in the UTC timezone.
     *
     * The migration must not be executed multiple times as it would remove the offset to match UTC again for the "recordedAt" datetime even if they are already meant to be UTC.
     * To prevent this from happening we compare the "recordedAt" and "initiatingTimeStamp" and if they are equal considering timezones we know the migration was run.
     *
     * Included in June 2026 - part of the bugfix 9.0.13, 9.1.6 and minor 9.2.0 release
     *
     * @param string $contentRepository Identifier of the Content Repository to migrate
     */
    public function migrateRecordedAtToUtcCommand(string $contentRepository = 'default', bool $force = false): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $eventMigrationService = $this->contentRepositoryRegistry->buildService($contentRepositoryId, $this->eventMigrationServiceFactory);
        $eventMigrationService->migrateRecordedAtToUtc($this->outputLine(...), $force);
    }
}
