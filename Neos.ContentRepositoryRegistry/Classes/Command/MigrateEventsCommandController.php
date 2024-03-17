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
     * Migrates "propertyValues":{"tagName":{"value":null,"type":"string"}} to "propertiesToUnset":["tagName"]
     *
     * Needed for #4322: https://github.com/neos/neos-development-collection/pull/4322
     *
     * Included in February 2024 - before final Neos 9.0 release
     *
     * @param string $contentRepository Identifier of the Content Repository to migrate
     */
    public function migratePropertiesToUnsetCommand(string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $eventMigrationService = $this->contentRepositoryRegistry->buildService($contentRepositoryId, $this->eventMigrationServiceFactory);
        $eventMigrationService->migratePropertiesToUnset($this->outputLine(...));
    }

    /**
     * Adds a dummy workspace name to the events meta-data, so it can be rebased
     *
     * Needed for #4708: https://github.com/neos/neos-development-collection/pull/4708
     *
     * Included in March 2024 - before final Neos 9.0 release
     *
     * @param string $contentRepository Identifier of the Content Repository to migrate
     */
    public function migrateMetaDataToWorkspaceNameCommand(string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $eventMigrationService = $this->contentRepositoryRegistry->buildService($contentRepositoryId, $this->eventMigrationServiceFactory);
        $eventMigrationService->migrateMetaDataToWorkspaceName($this->outputLine(...));
    }
}
