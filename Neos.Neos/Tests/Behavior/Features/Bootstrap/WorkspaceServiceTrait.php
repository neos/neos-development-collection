<?php
declare(strict_types=1);

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Behat\Gherkin\Node\TableNode;
use Neos\ContentRepository\BehavioralTests\TestSuite\Behavior\CRBehavioralTestsSubjectProvider;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\TestSuite\Behavior\Features\Bootstrap\Helpers\FakeUserIdProvider;
use Neos\Neos\Domain\Model\UserId;
use Neos\Neos\Domain\Model\WorkspaceDescription;
use Neos\Neos\Domain\Model\WorkspaceTitle;
use Neos\Neos\Domain\Service\WorkspaceService;
use Webmozart\Assert\Assert;

/**
 * Behat steps related to the {@see WorkspaceService}
 *
 * @internal only for behat tests within the Neos.Neos package
 */
trait WorkspaceServiceTrait
{
    use CRBehavioralTestsSubjectProvider;

    /**
     * @template T of object
     * @param class-string<T> $className
     *
     * @return T
     */
    abstract private function getObject(string $className): object;

    /**
     * @When the root workspace :workspaceName is created
     */
    public function theRootWorkspaceIsCreated(string $workspaceName): void
    {
        $this->getObject(WorkspaceService::class)->createRootWorkspace(
            $this->currentContentRepository->id,
            WorkspaceName::fromString($workspaceName),
            WorkspaceTitle::fromString($workspaceName),
            WorkspaceDescription::fromString(''),
        );
    }

    /**
     * @When the personal workspace :workspaceName is created with the target workspace :targetWorkspace
     */
    public function thePersonalWorkspaceIsCreatedWithTheTargetWorkspace(string $workspaceName, string $targetWorkspace): void
    {
        $this->getObject(WorkspaceService::class)->createPersonalWorkspace(
            $this->currentContentRepository->id,
            WorkspaceName::fromString($workspaceName),
            WorkspaceTitle::fromString($workspaceName),
            WorkspaceDescription::fromString(''),
            WorkspaceName::fromString($targetWorkspace),
            UserId::fromString(FakeUserIdProvider::$userId?->value ?? ''),
        );
    }

    /**
     * @When the shared workspace :workspaceName is created with the target workspace :targetWorkspace
     */
    public function theSharedWorkspaceIsCreatedWithTheTargetWorkspace(string $workspaceName, string $targetWorkspace): void
    {
        $this->getObject(WorkspaceService::class)->createSharedWorkspace(
            $this->currentContentRepository->id,
            WorkspaceName::fromString($workspaceName),
            WorkspaceTitle::fromString($workspaceName),
            WorkspaceDescription::fromString(''),
            WorkspaceName::fromString($targetWorkspace),
        );
    }

    /**
     * @Then the following workspaces should exist:
     */
    public function theFollowingWorkspacesShouldExist(TableNode $expectedWorkspacesTable): void
    {
        $expectedWorkspaces = $expectedWorkspacesTable->getHash();
        $actualWorkspaces = [];
        $workspaceFinder = $this->currentContentRepository->getWorkspaceFinder();
        $workspaceService = $this->getObject(WorkspaceService::class);
        foreach ($workspaceFinder->findAll() as $workspace) {
            $workspaceMetadata = $workspaceService->getWorkspaceMetadata($this->currentContentRepository->id, $workspace->workspaceName);
            $actualWorkspaces[] = [
                'Name' => $workspace->workspaceName->value,
                'Base workspace' => $workspace->baseWorkspaceName?->value ?? '',
                'Title' => $workspaceMetadata->title->value,
                'Classification' => $workspaceMetadata->classification->value,
            ];
        }
        Assert::same($expectedWorkspaces, $actualWorkspaces);
    }
}
