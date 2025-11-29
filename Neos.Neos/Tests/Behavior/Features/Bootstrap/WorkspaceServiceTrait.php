<?php

declare(strict_types=1);

/*
 * This file is part of the Neos.Neos package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\CRBehavioralTestsSubjectProvider;
use Neos\Neos\Domain\Model\UserId;
use Neos\Neos\Domain\Model\WorkspaceDescription;
use Neos\Neos\Domain\Model\WorkspaceRole;
use Neos\Neos\Domain\Model\WorkspaceRoleAssignment;
use Neos\Neos\Domain\Model\WorkspaceRoleAssignments;
use Neos\Neos\Domain\Model\WorkspaceRoleSubject;
use Neos\Neos\Domain\Model\WorkspaceRoleSubjectType;
use Neos\Neos\Domain\Model\WorkspaceTitle;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\Domain\Service\WorkspaceService;
use Neos\Neos\Security\Authorization\ContentRepositoryAuthorizationService;
use PHPUnit\Framework\Assert;

/**
 * Behat steps related to the {@see WorkspaceService}
 *
 * @internal only for behat tests within the Neos.Neos package
 */
trait WorkspaceServiceTrait
{
    use CRBehavioralTestsSubjectProvider;
    use ExceptionsTrait;

    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return T
     */
    abstract private function getObject(string $className): object;

    /**
     * @BeforeScenario
     */
    final public function pruneWorkspaceService(): void
    {
        foreach (static::$alreadySetUpContentRepositories as $contentRepositoryId) {
            $this->getObject(\Neos\Neos\Domain\Repository\WorkspaceMetadataAndRoleRepository::class)->pruneWorkspaceMetadata($contentRepositoryId);
            $this->getObject(\Neos\Neos\Domain\Repository\WorkspaceMetadataAndRoleRepository::class)->pruneRoleAssignments($contentRepositoryId);
        }
    }

    /**
     * @When the root workspace :workspaceName is created
     * @When the root workspace :workspaceName with title :title and description :description is created
     */
    public function theRootWorkspaceIsCreated(string $workspaceName, ?string $title = null, ?string $description = null): void
    {
        $this->tryCatchingExceptions(fn () => $this->getObject(WorkspaceService::class)->createRootWorkspace(
            $this->currentContentRepository->id,
            WorkspaceName::fromString($workspaceName),
            WorkspaceTitle::fromString($title ?? $workspaceName),
            WorkspaceDescription::fromString($description ?? ''),
            WorkspaceRoleAssignments::createEmpty()
        ));
    }

    /**
     * @When the workspace :workspaceName is deleted
     */
    public function theWorkspaceIsDeleted(string $workspaceName): void
    {
        $this->tryCatchingExceptions(fn () => $this->getObject(WorkspaceService::class)->deleteWorkspace(
            $this->currentContentRepository->id,
            WorkspaceName::fromString($workspaceName),
        ));
    }

    /**
     * @Given the live workspace exists
     */
    public function theLiveWorkspaceExists(): void
    {
        $this->getObject(WorkspaceService::class)->createRootWorkspace(
            $this->currentContentRepository->id,
            WorkspaceName::forLive(),
            WorkspaceTitle::fromString('Public live workspace'),
            WorkspaceDescription::createEmpty(),
            WorkspaceRoleAssignments::createForLiveWorkspace()
        );
    }

    /**
     * @When the personal workspace :workspaceName is created with the target workspace :targetWorkspace for user :username
     */
    public function thePersonalWorkspaceIsCreatedWithTheTargetWorkspace(string $workspaceName, string $targetWorkspace, string $username): void
    {
        $ownerUserId = $this->userIdForUsername($username);
        $this->tryCatchingExceptions(fn () => $this->getObject(WorkspaceService::class)->createPersonalWorkspace(
            $this->currentContentRepository->id,
            WorkspaceName::fromString($workspaceName),
            WorkspaceTitle::fromString($workspaceName),
            WorkspaceDescription::fromString(''),
            WorkspaceName::fromString($targetWorkspace),
            $ownerUserId,
        ));
    }

    /**
     * @Given a personal workspace for user :username is created
     */
    public function aPersonalWorkspaceForUserIsCreated(string $username): void
    {
        $user = $this->getObject(UserService::class)->getUser($username);
        $this->tryCatchingExceptions(fn () => $this->getObject(WorkspaceService::class)->createPersonalWorkspaceForUserIfMissing(
            $this->currentContentRepository->id,
            $user,
        ));
    }

    /**
     * @Then the personal workspace for user :username is :workspaceName
     */
    public function thePersonalWorkspaceForUserIs(string $username, string $workspaceName): void
    {
        $ownerUserId = $this->userIdForUsername($username);
        $actualWorkspace = $this->getObject(WorkspaceService::class)->getPersonalWorkspaceForUser($this->currentContentRepository->id, $ownerUserId);
        Assert::assertNotNull($actualWorkspace);
        Assert::assertSame($workspaceName, $actualWorkspace->workspaceName->value);
    }

    /**
     * @Then the user :username does not have a personal workspace
     */
    public function theUserDoesNotHaveAPersonalWorkspace(string $username): void
    {
        $ownerUserId = $this->userIdForUsername($username);
        try {
            $this->getObject(WorkspaceService::class)->getPersonalWorkspaceForUser($this->currentContentRepository->id, $ownerUserId);
        } catch (\Throwable $e) {
            // todo throw WorkspaceDoesNotExist instead??
            Assert::assertInstanceOf(\RuntimeException::class, $e, $e->getMessage());
            Assert::assertSame(1718293801, $e->getCode());
            return;
        }
        Assert::fail('Did not throw');
    }

    /**
     * @When the shared workspace :workspaceName is created with the target workspace :targetWorkspace
     * @When the shared workspace :workspaceName is created with the target workspace :targetWorkspace and role assignments:
     */
    public function theSharedWorkspaceIsCreatedWithTheTargetWorkspace(string $workspaceName, string $targetWorkspace, ?TableNode $rawRoleAssignments = null): void
    {
        $workspaceRoleAssignments = WorkspaceRoleAssignments::createEmpty();
        foreach ($rawRoleAssignments?->getHash() ?? [] as $row) {
            $workspaceRoleAssignments = $workspaceRoleAssignments->withAssignment(WorkspaceRoleAssignment::create(
                WorkspaceRoleSubject::create(
                    WorkspaceRoleSubjectType::from($row['Type']),
                    $row['Value']
                ),
                WorkspaceRole::from($row['Role'])
            ));
        }

        $this->tryCatchingExceptions(fn () => $this->getObject(WorkspaceService::class)->createSharedWorkspace(
            $this->currentContentRepository->id,
            WorkspaceName::fromString($workspaceName),
            WorkspaceTitle::fromString($workspaceName),
            WorkspaceDescription::fromString(''),
            WorkspaceName::fromString($targetWorkspace),
            $workspaceRoleAssignments
        ));
    }

    /**
     * @When a root workspace :workspaceName exists without metadata
     */
    public function aRootWorkspaceExistsWithoutMetadata(string $workspaceName): void
    {
        $this->currentContentRepository->handle(CreateRootWorkspace::create(
            WorkspaceName::fromString($workspaceName),
            ContentStreamId::create(),
        ));
    }

    /**
     * @When the title of workspace :workspaceName is set to :newTitle
     */
    public function theTitleOfWorkspaceIsSetTo(string $workspaceName, string $newTitle): void
    {
        $this->tryCatchingExceptions(fn () => $this->getObject(WorkspaceService::class)->setWorkspaceTitle(
            $this->currentContentRepository->id,
            WorkspaceName::fromString($workspaceName),
            WorkspaceTitle::fromString($newTitle),
        ));
    }

    /**
     * @When the description of workspace :workspaceName is set to :newDescription
     */
    public function theDescriptionOfWorkspaceIsSetTo(string $workspaceName, string $newDescription): void
    {
        $this->tryCatchingExceptions(fn () => $this->getObject(WorkspaceService::class)->setWorkspaceDescription(
            $this->currentContentRepository->id,
            WorkspaceName::fromString($workspaceName),
            WorkspaceDescription::fromString($newDescription),
        ));
    }

    /**
     * @Then the workspace :workspaceName should have the following metadata:
     */
    public function theWorkspaceShouldHaveTheFollowingMetadata($workspaceName, TableNode $expectedMetadata): void
    {
        $workspaceMetadata = $this->getObject(WorkspaceService::class)->getWorkspaceMetadata($this->currentContentRepository->id, WorkspaceName::fromString($workspaceName));
        Assert::assertSame($expectedMetadata->getHash()[0], [
            'Title' => $workspaceMetadata->title->value,
            'Description' => $workspaceMetadata->description->value,
            'Classification' => $workspaceMetadata->classification->value,
            'Owner user id' => $workspaceMetadata->ownerUserId?->value ?? '',
        ]);
    }

    /**
     * @Then the metadata for workspace :workspaceName does not exist
     */
    public function theWorkspaceMetadataFails($workspaceName): void
    {
        $metaData = $this->getObject(\Neos\Neos\Domain\Repository\WorkspaceMetadataAndRoleRepository::class)->loadWorkspaceMetadata($this->currentContentRepository->id, WorkspaceName::fromString($workspaceName));
        Assert::assertNull($metaData);

        // asking the API FAILS!
        try {
            $this->getObject(WorkspaceService::class)->getWorkspaceMetadata($this->currentContentRepository->id, WorkspaceName::fromString($workspaceName));
        } catch (\Throwable $e) {
            Assert::assertInstanceOf(WorkspaceDoesNotExist::class, $e, $e->getMessage());
            return;
        }
        Assert::fail('Did not throw');
    }

    /**
     * @Then the roles for workspace :workspaceName does not exist
     */
    public function theWorkspaceRolesFails($workspaceName): void
    {
        $roles = $this->getObject(\Neos\Neos\Domain\Repository\WorkspaceMetadataAndRoleRepository::class)->getWorkspaceRoleAssignments($this->currentContentRepository->id, WorkspaceName::fromString($workspaceName));
        Assert::assertTrue($roles->isEmpty());

        // asking the API FAILS!
        try {
            $this->getObject(WorkspaceService::class)->getWorkspaceRoleAssignments($this->currentContentRepository->id, WorkspaceName::fromString($workspaceName));
        } catch (\Throwable $e) {
            Assert::assertInstanceOf(WorkspaceDoesNotExist::class, $e, $e->getMessage());
            return;
        }
        Assert::fail('Did not throw');
    }

    /**
     * @When the role :role is assigned to workspace :workspaceName for group :groupName
     * @When the role :role is assigned to workspace :workspaceName for user :username
     */
    public function theRoleIsAssignedToWorkspaceForGroupOrUser(string $role, string $workspaceName, ?string $groupName = null, ?string $username = null): void
    {
        if ($groupName !== null) {
            $subject = WorkspaceRoleSubject::createForGroup($groupName);
        } else {
            $subject = WorkspaceRoleSubject::createForUser($this->userIdForUsername($username));
        }
        $this->tryCatchingExceptions(fn () => $this->getObject(WorkspaceService::class)->assignWorkspaceRole(
            $this->currentContentRepository->id,
            WorkspaceName::fromString($workspaceName),
            WorkspaceRoleAssignment::create(
                $subject,
                WorkspaceRole::from($role)
            )
        ));
    }

    /**
     * @When the role for group :groupName is unassigned from workspace :workspaceName
     * @When the role for user :username is unassigned from workspace :workspaceName
     */
    public function theRoleIsUnassignedFromWorkspace(string $workspaceName, ?string $groupName = null, ?string $username = null): void
    {
        if ($groupName !== null) {
            $subject = WorkspaceRoleSubject::createForGroup($groupName);
        } else {
            $subject = WorkspaceRoleSubject::createForUser($this->userIdForUsername($username));
        }
        $this->tryCatchingExceptions(fn () => $this->getObject(WorkspaceService::class)->unassignWorkspaceRole(
            $this->currentContentRepository->id,
            WorkspaceName::fromString($workspaceName),
            $subject,
        ));
    }

    /**
     * @Then the workspace :workspaceName should have the following role assignments:
     */
    public function theWorkspaceShouldHaveTheFollowingRoleAssignments($workspaceName, TableNode $expectedAssignments): void
    {
        $workspaceAssignments = $this->getObject(WorkspaceService::class)->getWorkspaceRoleAssignments($this->currentContentRepository->id, WorkspaceName::fromString($workspaceName));
        $actualAssignments = array_map(static fn (WorkspaceRoleAssignment $assignment) => [
            'Subject type' => $assignment->subject->type->value,
            'Subject' => $assignment->subject->value,
            'Role' => $assignment->role->value,
        ], iterator_to_array($workspaceAssignments));
        Assert::assertSame($expectedAssignments->getHash(), $actualAssignments);
    }

    /**
     * @Then the Neos user :username should have the permissions :expectedPermissions for workspace :workspaceName
     */
    public function theNeosUserShouldHaveThePermissionsForWorkspace(string $username, string $expectedPermissions, string $workspaceName): void
    {
        $userService = $this->getObject(UserService::class);
        $user = $userService->getUser($username);
        Assert::assertNotNull($user);
        $roles = $userService->getAllRoles($user);
        $permissions = $this->getObject(ContentRepositoryAuthorizationService::class)->getWorkspacePermissions(
            $this->currentContentRepository->id,
            WorkspaceName::fromString($workspaceName),
            $roles,
            $user->getId(),
        );
        Assert::assertSame($expectedPermissions, implode(',', array_keys(array_filter(get_object_vars($permissions)))));
    }

    /**
     * @Then the Neos user :username should have no permissions for workspace :workspaceName
     */
    public function theNeosUserShouldHaveNoPermissionsForWorkspace(string $username, string $workspaceName): void
    {
        $userService = $this->getObject(UserService::class);
        $user = $userService->getUser($username);
        Assert::assertNotNull($user);
        $roles = $userService->getAllRoles($user);
        $permissions = $this->getObject(ContentRepositoryAuthorizationService::class)->getWorkspacePermissions(
            $this->currentContentRepository->id,
            WorkspaceName::fromString($workspaceName),
            $roles,
            $user->getId(),
        );
        Assert::assertFalse($permissions->read);
        Assert::assertFalse($permissions->write);
        Assert::assertFalse($permissions->manage);
    }

    private function userIdForUsername(string $username): UserId
    {
        $user = $this->getObject(UserService::class)->getUser($username);
        Assert::assertNotNull($user, sprintf('The user "%s" does not exist', $username));
        return $user->getId();
    }
}
