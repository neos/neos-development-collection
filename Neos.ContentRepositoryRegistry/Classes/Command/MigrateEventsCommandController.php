<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Command;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Service\EventMigrationServiceFactory;
use Neos\Flow\Cli\CommandController;

class MigrateEventsCommandController extends CommandController
{
    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        private readonly EventMigrationServiceFactory $eventMigrationServiceFactory
    ) {
        parent::__construct();
    }

    /**
     * Migrates initial metadata & roles from the CR core workspaces to the corresponding Neos database tables
     *
     * Needed to extract these information to Neos.Neos: https://github.com/neos/neos-development-collection/issues/4726
     *
     * Included in September 2024 - before final Neos 9.0 release
     *
     * @param string $contentRepository Identifier of the Content Repository to migrate
     */
    public function migrateWorkspaceMetadataToWorkspaceServiceCommand(string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $eventMigrationService = $this->contentRepositoryRegistry->buildService($contentRepositoryId, $this->eventMigrationServiceFactory);
        $eventMigrationService->migrateWorkspaceMetadataToWorkspaceService($this->outputLine(...));
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

    /**
     * Adds the "workspaceName" to the data of all content stream related events
     *
     * Needed for feature "Add workspaceName to relevant events": https://github.com/neos/neos-development-collection/issues/4996
     *
     * Included in May 2024 - before final Neos 9.0 release
     *
     * @param string $contentRepository Identifier of the Content Repository to migrate
     */
    public function migratePayloadToWorkspaceNameCommand(string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $eventMigrationService = $this->contentRepositoryRegistry->buildService($contentRepositoryId, $this->eventMigrationServiceFactory);
        $eventMigrationService->migratePayloadToWorkspaceName($this->outputLine(...));
    }

    /**
     * Rewrites all workspaceNames, that are not matching new constraints.
     *
     * Needed for feature "Stabilize WorkspaceName value object": https://github.com/neos/neos-development-collection/pull/5193
     *
     * Included in August 2024 - before final Neos 9.0 release
 *
     * @param string $contentRepository Identifier of the Content Repository to migrate
     */
    public function migratePayloadToValidWorkspaceNamesCommand(string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $eventMigrationService = $this->contentRepositoryRegistry->buildService($contentRepositoryId, $this->eventMigrationServiceFactory);
        $eventMigrationService->migratePayloadToValidWorkspaceNames($this->outputLine(...));
    }

    public function migrateSetReferencesToMultiNameFormatCommand(string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $eventMigrationService = $this->contentRepositoryRegistry->buildService($contentRepositoryId, $this->eventMigrationServiceFactory);
        $eventMigrationService->migrateReferencesToMultiFormat($this->outputLine(...));
    }
}
