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
     * Detects if the ATOM stored "initiatingTimeStamp" has a uniform offset which is not UTC (0)
     * Then all "recordedAt" times are assumed to be in that timezone and their timestamp adjusted to match UTC
     *
     * If the server timezone changed multiple times for the events the migration will not applied.
     *
     * Included in June 2026 - part of the bugfix 9.0.13, 9.1.6 and minor 9.2.0 release
     *
     * @param string $contentRepository Identifier of the Content Repository to migrate
     */
    public function migrateRecordedAtToUtcCommand(string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $eventMigrationService = $this->contentRepositoryRegistry->buildService($contentRepositoryId, $this->eventMigrationServiceFactory);
        $eventMigrationService->migrateRecordedAtToUtc($this->outputLine(...));
    }
}
