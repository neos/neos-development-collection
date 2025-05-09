<?php

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\Domain\Service;

use Neos\ContentRepository\Core\Feature\Security\Exception\AccessDenied;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Exception\WorkspaceAlreadyExists;
use Neos\ContentRepository\Core\Feature\WorkspaceModification\Command\DeleteWorkspace;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\Workspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Context as SecurityContext;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Model\UserId;
use Neos\Neos\Domain\Model\WorkspaceClassification;
use Neos\Neos\Domain\Model\WorkspaceDescription;
use Neos\Neos\Domain\Model\WorkspaceMetadata;
use Neos\Neos\Domain\Model\WorkspaceRole;
use Neos\Neos\Domain\Model\WorkspaceRoleAssignment;
use Neos\Neos\Domain\Model\WorkspaceRoleAssignments;
use Neos\Neos\Domain\Model\WorkspaceRoleSubject;
use Neos\Neos\Domain\Model\WorkspaceTitle;
use Neos\Neos\Domain\Repository\WorkspaceMetadataAndRoleRepository;
use Neos\Neos\Domain\SubtreeTagging\SoftRemoval\SoftRemovalGarbageCollector;
use Neos\Neos\Security\Authorization\ContentRepositoryAuthorizationService;

/**
 * Central authority to interact with Content Repository Workspaces within Neos
 *
 * @api
 */
#[Flow\Scope('singleton')]
final readonly class WorkspaceService
{
    public function __construct(
        private ContentRepositoryRegistry $contentRepositoryRegistry,
        private WorkspaceMetadataAndRoleRepository $metadataAndRoleRepository,
        private UserService $userService,
        private ContentRepositoryAuthorizationService $authorizationService,
        private SecurityContext $securityContext,
        private SoftRemovalGarbageCollector $softRemovalGarbageCollector,
    ) {
    }

    /**
     * Load metadata for the specified workspace
     *
     * Note: If no metadata exists for the specified workspace, metadata with title based on the name and classification
     * according to the content repository workspace is returned. Root workspaces are of classification ROOT whereas simple ones will yield UNKNOWN.
     * {@see WorkspaceClassification::UNKNOWN}
     */
    public function getWorkspaceMetadata(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): WorkspaceMetadata
    {
        $workspace = $this->requireWorkspace($contentRepositoryId, $workspaceName);
        $metadata = $this->metadataAndRoleRepository->loadWorkspaceMetadata($contentRepositoryId, $workspaceName);
        return $metadata ?? new WorkspaceMetadata(
            WorkspaceTitle::fromString($workspaceName->value),
            WorkspaceDescription::fromString(''),
            $workspace->isRootWorkspace() ? WorkspaceClassification::ROOT : WorkspaceClassification::UNKNOWN,
            null,
        );
    }

    /**
     * Update/set title metadata for the specified workspace
     */
    public function setWorkspaceTitle(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceTitle $newWorkspaceTitle): void
    {
        $this->requireManagementWorkspacePermission($contentRepositoryId, $workspaceName);
        $workspace = $this->requireWorkspace($contentRepositoryId, $workspaceName);
        $this->metadataAndRoleRepository->updateWorkspaceMetadata($contentRepositoryId, $workspace, title: $newWorkspaceTitle->value, description: null);
    }

    /**
     * Update/set description metadata for the specified workspace
     */
    public function setWorkspaceDescription(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceDescription $newWorkspaceDescription): void
    {
        $this->requireManagementWorkspacePermission($contentRepositoryId, $workspaceName);
        $workspace = $this->requireWorkspace($contentRepositoryId, $workspaceName);
        $this->metadataAndRoleRepository->updateWorkspaceMetadata($contentRepositoryId, $workspace, title: null, description: $newWorkspaceDescription->value);
    }

    /**
     * Retrieve the personal workspace for the specified user, if no workspace exist an exception is thrown.
     */
    public function getPersonalWorkspaceForUser(ContentRepositoryId $contentRepositoryId, UserId $userId): Workspace
    {
        $workspaceName = $this->metadataAndRoleRepository->findWorkspaceNameByUser($contentRepositoryId, $userId);
        if ($workspaceName === null) {
            throw new \RuntimeException(sprintf('No workspace is assigned to the user with id "%s")', $userId->value), 1718293801);
        }
        return $this->requireWorkspace($contentRepositoryId, $workspaceName);
    }

    /**
     * Create a new root (aka base) workspace with the specified metadata
     *
     * To ensure that editors can publish to the live workspace and to allow everybody to view it an assignment like {@see WorkspaceRoleAssignments::createForLiveWorkspace} needs to be specified:
     *
     *     $this->workspaceService->createRootWorkspace(
     *         $contentRepositoryId,
     *         WorkspaceName::forLive(),
     *         WorkspaceTitle::fromString('Public live workspace'),
     *         WorkspaceDescription::empty(),
     *         WorkspaceRoleAssignments::createForLiveWorkspace()
     *     );
     *
     * @throws WorkspaceAlreadyExists
     */
    public function createRootWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceTitle $title, WorkspaceDescription $description, WorkspaceRoleAssignments $assignments): void
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $contentRepository->handle(
            CreateRootWorkspace::create(
                $workspaceName,
                ContentStreamId::create()
            )
        );

        $this->metadataAndRoleRepository->transactional(function () use ($contentRepositoryId, $workspaceName, $title, $description, $assignments) {
            $this->metadataAndRoleRepository->addWorkspaceMetadata($contentRepositoryId, $workspaceName, $title, $description, WorkspaceClassification::ROOT, ownerUserId: null);
            foreach ($assignments as $assignment) {
                $this->metadataAndRoleRepository->assignWorkspaceRole($contentRepositoryId, $workspaceName, $assignment);
            }
        });
    }

    /**
     * Create a new, personal, workspace for the specified user (fails if the user already owns a workspace)
     */
    public function createPersonalWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceTitle $title, WorkspaceDescription $description, WorkspaceName $baseWorkspaceName, UserId $ownerId): void
    {
        $existingUserWorkspace = $this->metadataAndRoleRepository->findWorkspaceNameByUser($contentRepositoryId, $ownerId);
        if ($existingUserWorkspace !== null) {
            throw new \RuntimeException(sprintf('Failed to create personal workspace "%s" for user with id "%s", because the workspace "%s" is already assigned to the user', $workspaceName->value, $ownerId->value, $existingUserWorkspace->value), 1733754904);
        }
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $contentRepository->handle(
            CreateWorkspace::create(
                $workspaceName,
                $baseWorkspaceName,
                ContentStreamId::create()
            )
        );
        $this->metadataAndRoleRepository->addWorkspaceMetadata($contentRepositoryId, $workspaceName, $title, $description, WorkspaceClassification::PERSONAL, $ownerId);
    }

    /**
     * Create a new, potentially shared, workspace
     *
     * To ensure that the user can manage the shared workspace and to enable collaborates an assignment like {@see WorkspaceRoleAssignments::createForSharedWorkspace} needs to be specified:
     *
     *     $this->workspaceService->createWorkspace(
     *         ...,
     *         assignments: WorkspaceRoleAssignments::createForSharedWorkspace(
     *             $currentUser->getId()
     *         )
     *     );
     *
     * NOTE: By default - if no role assignments are specified - only administrators can manage workspaces without role assignments.
     */
    public function createSharedWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceTitle $title, WorkspaceDescription $description, WorkspaceName $baseWorkspaceName, WorkspaceRoleAssignments $assignments): void
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $contentRepository->handle(
            CreateWorkspace::create(
                $workspaceName,
                $baseWorkspaceName,
                ContentStreamId::create()
            )
        );

        $this->metadataAndRoleRepository->transactional(function () use ($contentRepositoryId, $workspaceName, $title, $description, $assignments) {
            $this->metadataAndRoleRepository->addWorkspaceMetadata($contentRepositoryId, $workspaceName, $title, $description, WorkspaceClassification::SHARED, ownerUserId: null);
            foreach ($assignments as $assignment) {
                $this->metadataAndRoleRepository->assignWorkspaceRole($contentRepositoryId, $workspaceName, $assignment);
            }
        });
    }

    /**
     * Create a new, personal, workspace for the specified user if none exists yet
     */
    public function createPersonalWorkspaceForUserIfMissing(ContentRepositoryId $contentRepositoryId, User $user): void
    {
        $existingWorkspaceName = $this->metadataAndRoleRepository->findWorkspaceNameByUser($contentRepositoryId, $user->getId());
        if ($existingWorkspaceName !== null) {
            $this->requireWorkspace($contentRepositoryId, $existingWorkspaceName);
            return;
        }
        $workspaceName = $this->getUniqueWorkspaceName($contentRepositoryId, $user->getLabel());
        $this->createPersonalWorkspace(
            $contentRepositoryId,
            $workspaceName,
            WorkspaceTitle::fromString($user->getLabel()),
            WorkspaceDescription::createEmpty(),
            WorkspaceName::forLive(),
            $user->getId(),
        );
    }

    /**
     * Assign a workspace role to the given user/user group
     *
     * Without explicit workspace roles, only administrators can change the corresponding workspace.
     * With this method, the subject (i.e. a Neos user or group represented by a Flow role identifier) can be granted a {@see WorkspaceRole} for the specified workspace
     */
    public function assignWorkspaceRole(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceRoleAssignment $assignment): void
    {
        $this->requireManagementWorkspacePermission($contentRepositoryId, $workspaceName);
        $this->requireWorkspace($contentRepositoryId, $workspaceName);
        $this->metadataAndRoleRepository->assignWorkspaceRole($contentRepositoryId, $workspaceName, $assignment);
    }

    /**
     * Remove a workspace role assignment for the given subject
     *
     * @see self::assignWorkspaceRole()
     */
    public function unassignWorkspaceRole(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName, WorkspaceRoleSubject $subject): void
    {
        $this->requireManagementWorkspacePermission($contentRepositoryId, $workspaceName);
        $this->requireWorkspace($contentRepositoryId, $workspaceName);
        $this->metadataAndRoleRepository->unassignWorkspaceRole($contentRepositoryId, $workspaceName, $subject);
    }

    /**
     * Deletes a content repository workspace and also all role assignments and metadata
     */
    public function deleteWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): void
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $this->requireWorkspace($contentRepositoryId, $workspaceName);

        $contentRepository->handle(
            DeleteWorkspace::create(
                $workspaceName
            )
        );

        $this->metadataAndRoleRepository->deleteWorkspaceMetadata($contentRepositoryId, $workspaceName);
        $this->metadataAndRoleRepository->deleteWorkspaceRoleAssignments($contentRepositoryId, $workspaceName);

        $this->softRemovalGarbageCollector->run($contentRepositoryId);
    }

    /**
     * Get all role assignments for the specified workspace
     *
     * NOTE: This should never be used to evaluate permissions, instead {@see ContentRepositoryAuthorizationService::getWorkspacePermissions()} should be used!
     */
    public function getWorkspaceRoleAssignments(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): WorkspaceRoleAssignments
    {
        $this->requireWorkspace($contentRepositoryId, $workspaceName);
        return $this->metadataAndRoleRepository->getWorkspaceRoleAssignments($contentRepositoryId, $workspaceName);
    }

    /**
     * Builds a workspace name that is unique within the specified content repository.
     * If $candidate already refers to a workspace name that is not used yet, it will be used (with transliteration to enforce a valid format)
     * Otherwise a counter "-n" suffix is appended and increased until a unique name is found, or the maximum number of attempts has been reached (in which case an exception is thrown)
     */
    public function getUniqueWorkspaceName(ContentRepositoryId $contentRepositoryId, string $candidate): WorkspaceName
    {
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $workspaceNameCandidate = WorkspaceName::transliterateFromString($candidate);
        $workspaceName = $workspaceNameCandidate;
        $attempt = 1;
        do {
            if ($contentRepository->findWorkspaceByName($workspaceName) === null) {
                return $workspaceName;
            }
            if ($attempt === 1) {
                $suffix = '';
            } else {
                $suffix = '-' . ($attempt - 1);
            }
            $workspaceName = WorkspaceName::fromString(
                substr($workspaceNameCandidate->value, 0, WorkspaceName::MAX_LENGTH - strlen($suffix)) . $suffix
            );
            $attempt++;
        } while ($attempt <= 10);
        throw new \RuntimeException(sprintf('Failed to find unique workspace name for "%s" after %d attempts.', $candidate, $attempt - 1), 1725975479);
    }

    // ------------------

    /**
     * @throws WorkspaceDoesNotExist if the workspace does not exist
     */
    private function requireWorkspace(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): Workspace
    {
        $workspace = $this->contentRepositoryRegistry
            ->get($contentRepositoryId)
            ->findWorkspaceByName($workspaceName);
        if ($workspace === null) {
            throw WorkspaceDoesNotExist::butWasSupposedToInContentRepository($workspaceName, $contentRepositoryId);
        }
        return $workspace;
    }

    private function requireManagementWorkspacePermission(ContentRepositoryId $contentRepositoryId, WorkspaceName $workspaceName): void
    {
        if ($this->securityContext->areAuthorizationChecksDisabled()) {
            return;
        }
        $workspacePermissions = $this->authorizationService->getWorkspacePermissions(
            $contentRepositoryId,
            $workspaceName,
            $this->securityContext->getRoles(),
            $this->userService->getCurrentUser()?->getId()
        );
        if (!$workspacePermissions->manage) {
            throw new AccessDenied(sprintf('Managing workspace "%s" in "%s" was denied: %s', $workspaceName->value, $contentRepositoryId->value, $workspacePermissions->getReason()), 1731654519);
        }
    }
}
