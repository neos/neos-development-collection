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
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\CRBehavioralTestsSubjectProvider;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateRootWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription as DeprecatedWorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle as DeprecatedWorkspaceTitle;
use Neos\Neos\Domain\Model\UserId;
use Neos\Neos\Domain\Model\WorkspaceDescription;
use Neos\Neos\Domain\Model\WorkspaceRole;
use Neos\Neos\Domain\Model\WorkspaceRoleAssignment;
use Neos\Neos\Domain\Model\WorkspaceRoleSubject;
use Neos\Neos\Domain\Model\WorkspaceRoleSubjectType;
use Neos\Neos\Domain\Model\WorkspaceTitle;
use Neos\Neos\Domain\Service\WorkspaceService;
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
     * @When the root workspace :workspaceName is created
     * @When the root workspace :workspaceName with title :title and description :description is created
     */
    public function theRootWorkspaceIsCreated(string $workspaceName, string $title = null, string $description = null): void
    {
        $this->tryCatchingExceptions(fn () => $this->getObject(WorkspaceService::class)->createRootWorkspace(
            $this->currentContentRepository->id,
            WorkspaceName::fromString($workspaceName),
            WorkspaceTitle::fromString($title ?? $workspaceName),
            WorkspaceDescription::fromString($description ?? ''),
        ));
    }

    /**
     * @When the personal workspace :workspaceName is created with the target workspace :targetWorkspace for user :ownerUserId
     */
    public function thePersonalWorkspaceIsCreatedWithTheTargetWorkspace(string $workspaceName, string $targetWorkspace, string $ownerUserId): void
    {
        $this->tryCatchingExceptions(fn () => $this->getObject(WorkspaceService::class)->createPersonalWorkspace(
            $this->currentContentRepository->id,
            WorkspaceName::fromString($workspaceName),
            WorkspaceTitle::fromString($workspaceName),
            WorkspaceDescription::fromString(''),
            WorkspaceName::fromString($targetWorkspace),
            UserId::fromString($ownerUserId),
        ));
    }

    /**
     * @When the shared workspace :workspaceName is created with the target workspace :targetWorkspace
     */
    public function theSharedWorkspaceIsCreatedWithTheTargetWorkspace(string $workspaceName, string $targetWorkspace): void
    {
        $this->tryCatchingExceptions(fn () => $this->getObject(WorkspaceService::class)->createSharedWorkspace(
            $this->currentContentRepository->id,
            WorkspaceName::fromString($workspaceName),
            WorkspaceTitle::fromString($workspaceName),
            WorkspaceDescription::fromString(''),
            WorkspaceName::fromString($targetWorkspace),
        ));
    }

    /**
     * @When a root workspace :workspaceName exists without metadata
     */
    public function aRootWorkspaceExistsWithoutMetadata(string $workspaceName): void
    {
        $this->currentContentRepository->handle(CreateRootWorkspace::create(
            WorkspaceName::fromString($workspaceName),
            DeprecatedWorkspaceTitle::fromString($workspaceName),
            DeprecatedWorkspaceDescription::fromString(''),
            ContentStreamId::create(),
        ));
    }

    /**
     * @When a workspace :arg1 with base workspace :arg2 exists without metadata
     */
    public function aWorkspaceWithBaseWorkspaceExistsWithoutMetadata(string $workspaceName, string $baseWorkspaceName): void
    {
        $this->currentContentRepository->handle(CreateWorkspace::create(
            WorkspaceName::fromString($workspaceName),
            WorkspaceName::fromString($baseWorkspaceName),
            DeprecatedWorkspaceTitle::fromString($workspaceName),
            DeprecatedWorkspaceDescription::fromString(''),
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
     * @When the role :role is assigned to workspace :workspaceName for group :groupName
     * @When the role :role is assigned to workspace :workspaceName for user :username
     */
    public function theRoleIsAssignedToWorkspaceForGroupOrUser(string $role, string $workspaceName, string $groupName = null, string $username = null): void
    {
        $this->tryCatchingExceptions(fn () => $this->getObject(WorkspaceService::class)->assignWorkspaceRole(
            $this->currentContentRepository->id,
            WorkspaceName::fromString($workspaceName),
            WorkspaceRoleAssignment::create(
                $groupName !== null ? WorkspaceRoleSubjectType::GROUP : WorkspaceRoleSubjectType::USER,
                WorkspaceRoleSubject::fromString($groupName ?? $username),
                WorkspaceRole::from($role)
            )
        ));
    }

    /**
     * @When the role for group :groupName is unassigned from workspace :workspaceName
     * @When the role for user :username is unassigned from workspace :workspaceName
     */
    public function theRoleIsUnassignedFromWorkspace(string $workspaceName, string $groupName = null, string $username = null): void
    {
        $this->tryCatchingExceptions(fn () => $this->getObject(WorkspaceService::class)->unassignWorkspaceRole(
            $this->currentContentRepository->id,
            WorkspaceName::fromString($workspaceName),
            $groupName !== null ? WorkspaceRoleSubjectType::GROUP : WorkspaceRoleSubjectType::USER,
            WorkspaceRoleSubject::fromString($groupName ?? $username),
        ));
    }

    /**
     * @Then the workspace :workspaceName should have the following role assignments:
     */
    public function theWorkspaceShouldHaveTheFollowingRoleAssignments($workspaceName, TableNode $expectedAssignments): void
    {
        $workspaceAssignments = $this->getObject(WorkspaceService::class)->getWorkspaceRoleAssignments($this->currentContentRepository->id, WorkspaceName::fromString($workspaceName));
        $actualAssignments = array_map(static fn (WorkspaceRoleAssignment $assignment) => [
            'Subject type' => $assignment->subjectType->value,
            'Subject' => $assignment->subject->value,
            'Role' => $assignment->role->value,
        ], iterator_to_array($workspaceAssignments));
        Assert::assertSame($expectedAssignments->getHash(), $actualAssignments);
    }
}
