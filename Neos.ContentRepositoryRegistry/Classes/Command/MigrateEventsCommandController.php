<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Command;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Service\EventMigrationServiceFactory;
use Neos\Flow\Cli\CommandController;
use Neos\Neos\Domain\Model\WorkspaceClassification;
use Neos\Neos\Domain\Model\WorkspaceRole;
use Neos\Neos\Domain\Model\WorkspaceSubjectType;
use Neos\Neos\Domain\Service\WorkspaceService;

class MigrateEventsCommandController extends CommandController
{
    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        private readonly EventMigrationServiceFactory $eventMigrationServiceFactory,
        private readonly WorkspaceService $workspaceService,
        private readonly Connection $dbal,
    ) {
        parent::__construct();
    }

    /**
     * Migrates initial metadata & roles from the CR core workspaces to the corresponding Neos database tables
     *
     * @see https://github.com/neos/neos-development-collection/pull/5146
     *
     */
    public function workspacesCommand(string $contentRepository = 'default'): void
    {
        $contentRepositoryId = ContentRepositoryId::fromString($contentRepository);
        $contentRepositoryInstance = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $workspaces = $contentRepositoryInstance->getWorkspaceFinder()->findAll();

        if (count($workspaces) === 0) {
            $this->outputLine('No workspaces found.');
            $this->quit();
        }
        foreach ($workspaces as $workspace) {
            try {
                $this->workspaceService->getWorkspaceMetadata($contentRepositoryId, $workspace->workspaceName);
            } catch (\Exception $e) {
                if ($workspace->baseWorkspaceName === null) {
                    $classification = WorkspaceClassification::ROOT;
                } elseif ($workspace->isPersonalWorkspace()) {
                    $classification = WorkspaceClassification::PERSONAL;
                } else {
                    $classification = WorkspaceClassification::SHARED;
                }
                $this->dbal->insert('neos_neos_workspace_metadata', [
                    'content_repository_id' => $contentRepositoryId->value,
                    'workspace_name' => $workspace->workspaceName->value,
                    'title' => $workspace->workspaceTitle->value,
                    'description' => $workspace->workspaceDescription->value,
                    'classification' => $classification->value,
                    'owner_user_id' => $workspace->isPersonalWorkspace() ? $workspace->workspaceOwner : null,
                ]);
                if ($workspace->workspaceName->isLive()) {
                    $this->dbal->insert('neos_neos_workspace_role', [
                        'content_repository_id' => $contentRepositoryId->value,
                        'workspace_name' => $workspace->workspaceName->value,
                        'subject_type' => WorkspaceSubjectType::GROUP->value,
                        'subject' => 'Neos.Neos:LivePublisher',
                        'role' => WorkspaceRole::COLLABORATOR->value,
                    ]);
                } elseif ($workspace->isInternalWorkspace()) {
                    $this->dbal->insert('neos_neos_workspace_role', [
                        'content_repository_id' => $contentRepositoryId->value,
                        'workspace_name' => $workspace->workspaceName->value,
                        'subject_type' => WorkspaceSubjectType::GROUP->value,
                        'subject' => 'Neos.Neos:AbstractEditor',
                        'role' => WorkspaceRole::COLLABORATOR->value,
                    ]);
                } elseif ($workspace->isPrivateWorkspace()) {
                    $this->dbal->insert('neos_neos_workspace_role', [
                        'content_repository_id' => $contentRepositoryId->value,
                        'workspace_name' => $workspace->workspaceName->value,
                        'subject_type' => WorkspaceSubjectType::USER->value,
                        'subject' => $workspace->workspaceOwner,
                        'role' => WorkspaceRole::COLLABORATOR->value,
                    ]);
                }
                $this->outputLine('Added metadata for workspace "%s"', [$workspace->workspaceName->value]);
            }
        }
        $this->outputLine('Done.');
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
}
