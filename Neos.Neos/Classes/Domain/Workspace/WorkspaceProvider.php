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

namespace Neos\Neos\Domain\Workspace;

use Doctrine\Common\Collections\Collection;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace as ContentRepositoryWorkspace;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Security\Account;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Service\UserService;
use Neos\Neos\Domain\Service\WorkspaceNameBuilder;

/**
 * Neos' provider for its own workspace instances
 *
 * @api
 */
#[Flow\Scope('singleton')]
final class WorkspaceProvider
{
    /**
     * @var array<string, Workspace>
     */
    private array $instances;

    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
        private readonly WorkspaceAssignmentDirectory $workspaceAssignmentDirectory,
        private readonly UserService $userService,
    ) {
    }

    public function provideForWorkspaceName(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName $workspaceName,
    ): Workspace {
        $index = $contentRepositoryId->value . '-' . $workspaceName->value;
        if (isset($this->instances[$index])) {
            return $this->instances[$index];
        }

        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);
        $contentRepositoryWorkspace = $this->requireContentRepositoryWorkspace($contentRepository, $workspaceName);

        return $this->instances[$index] = new Workspace(
            $workspaceName,
            $contentRepositoryWorkspace->currentContentStreamId,
            $contentRepositoryWorkspace->status,
            $contentRepositoryWorkspace->baseWorkspaceName,
            $contentRepository
        );
    }

    public function providePrimaryPersonalWorkspaceForCurrentUser(
        ContentRepositoryId $contentRepositoryId,
    ): Workspace {
        $user = $this->userService->getCurrentUser();
        if ($user === null) {
            throw new \Exception('No user is logged in');
        }

        return $this->providePrimaryPersonalWorkspaceForUser($contentRepositoryId, $user);
    }

    public function providePrimaryPersonalWorkspaceForUser(
        ContentRepositoryId $contentRepositoryId,
        User $user
    ): Workspace {
        /** @var Collection<int,Account> $accounts */
        $accounts = $user->getAccounts();
        $primaryAccount = $accounts->first() ?: null;
        if ($primaryAccount === null) {
            throw new \DomainException('User ' . $user->getLabel() . ' has no accounts', 1714077323);
        }
        $contentRepository = $this->contentRepositoryRegistry->get($contentRepositoryId);

        $workspaceName = $this->workspaceAssignmentDirectory->findPrimaryPersonalWorkspaceName($contentRepositoryId, $user);
        if (!$workspaceName) {
            $contentRepositoryWorkspace = $this->createContentRepositoryWorkspaceForAccount($contentRepository, $primaryAccount);
            $this->workspaceAssignmentDirectory->assignWorkspaceToUser(
                $contentRepositoryId,
                $contentRepositoryWorkspace->workspaceName,
                $user,
                WorkspaceClassification::CLASSIFICATION_PRIMARY_PERSONAL
            );

            return $this->provideForContentRepositoryWorkspace(
                $contentRepository,
                $contentRepositoryWorkspace
            );
        }

        $contentRepositoryWorkspace = $contentRepository->getWorkspaceFinder()->findOneByName($workspaceName);
        if (!$contentRepositoryWorkspace instanceof ContentRepositoryWorkspace) {
            $contentRepositoryWorkspace = $this->createContentRepositoryWorkspaceForAccount($contentRepository, $primaryAccount);
        }

        return $this->provideForContentRepositoryWorkspace(
            $contentRepository,
            $contentRepositoryWorkspace,
        );
    }

    private function createContentRepositoryWorkspaceForAccount(
        ContentRepository $contentRepository,
        Account $primaryAccount,
    ): ContentRepositoryWorkspace {
        $workspaceName = WorkspaceNameBuilder::fromAccountIdentifier($primaryAccount->getAccountIdentifier());
        $contentRepository->handle(CreateWorkspace::create(
            $workspaceName,
            WorkspaceName::forLive(),
            WorkspaceTitle::fromString('Personal workspace for ' . $primaryAccount->getAccountIdentifier()),
            WorkspaceDescription::fromString('Personal workspace for ' . $primaryAccount->getAccountIdentifier()),
            ContentStreamId::create()
        ))->block();
        $contentRepositoryWorkspace = $contentRepository->getWorkspaceFinder()->findOneByName($workspaceName);
        assert($contentRepositoryWorkspace instanceof ContentRepositoryWorkspace);

        return $contentRepositoryWorkspace;
    }

    private function provideForContentRepositoryWorkspace(
        ContentRepository $contentRepository,
        ContentRepositoryWorkspace $contentRepositoryWorkspace,
    ): Workspace {
        $index = $contentRepository->id->value . '-' . $contentRepositoryWorkspace->workspaceName->value;
        if (isset($this->instances[$index])) {
            return $this->instances[$index];
        }

        return $this->instances[$index] = new Workspace(
            $contentRepositoryWorkspace->workspaceName,
            $contentRepositoryWorkspace->currentContentStreamId,
            $contentRepositoryWorkspace->status,
            $contentRepositoryWorkspace->baseWorkspaceName,
            $contentRepository
        );
    }

    private function requireContentRepositoryWorkspace(
        ContentRepository $contentRepository,
        WorkspaceName $workspaceName
    ): ContentRepositoryWorkspace {
        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName($workspaceName);
        if (!$workspace instanceof ContentRepositoryWorkspace) {
            throw WorkspaceDoesNotExist::butWasSupposedTo($workspaceName);
        }

        // @todo: access control goes here

        return $workspace;
    }
}
