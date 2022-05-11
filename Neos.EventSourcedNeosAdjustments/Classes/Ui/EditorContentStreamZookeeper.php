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

namespace Neos\EventSourcedNeosAdjustments\Ui;

use Neos\ContentRepository\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Feature\WorkspaceCommandHandler;
use Neos\ContentRepository\Projection\Workspace\Workspace;
use Neos\ContentRepository\Projection\Workspace\WorkspaceFinder;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceTitle;
use Neos\Neos\Domain\Model\WorkspaceName as AdjustmentsWorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Security\Authentication;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Security\Policy\Role;
use Neos\Neos\Domain\Model\User;
use Neos\Party\Domain\Service\PartyService;

/**
 * The service for keeping track of editors' content streams
 *
 * On authentication, workspaces may have to be created and content streams may have to be forked from live
 * or rebased from older ones
 *
 * @Flow\Scope("singleton")
 */
final class EditorContentStreamZookeeper
{
    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var PartyService
     */
    protected $partyService;

    /**
     * @Flow\Inject
     * @var PolicyService
     */
    protected $policyService;

    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    /**
     * @Flow\Inject
     * @var WorkspaceCommandHandler
     */
    protected $workspaceCommandHandler;

    /**
     * This method is called whenever a login happens (AuthenticationProviderManager::class, 'authenticatedToken'),
     * using Signal/Slot
     *
     * @param Authentication\TokenInterface $token
     * @throws \Exception
     * @throws \Neos\ContentRepository\Feature\WorkspaceCreation\Exception\BaseWorkspaceDoesNotExist
     * @throws \Neos\ContentRepository\Feature\WorkspaceCreation\Exception\WorkspaceAlreadyExists
     * @throws \Neos\ContentRepository\Feature\Common\Exception\WorkspaceDoesNotExist
     * @throws \Neos\Flow\Persistence\Exception\InvalidQueryException
     */
    public function relayEditorAuthentication(Authentication\TokenInterface $token): void
    {
        $isEditor = false;
        foreach ($token->getAccount()->getRoles() as $role) {
            /** @var Role $role */
            if (isset($role->getAllParentRoles()['Neos.Neos:AbstractEditor'])) {
                $isEditor = true;
                break;
            }
        }

        if ($isEditor) {
            $user = $this->partyService->getAssignedPartyOfAccount($token->getAccount());
            if ($user instanceof User) {
                $workspaceName = AdjustmentsWorkspaceName::fromAccountIdentifier(
                    $token->getAccount()->getAccountIdentifier()
                );
                $workspace = $this->workspaceFinder->findOneByName($workspaceName->toContentRepositoryWorkspaceName());

                $userIdentifier = UserIdentifier::fromString($this->persistenceManager->getIdentifierByObject($user));
                if (!$workspace) {
                    // @todo: find base workspace for user
                    /** @var Workspace $baseWorkspace */
                    $baseWorkspace = $this->workspaceFinder->findOneByName(WorkspaceName::forLive());
                    $editorsNewContentStreamIdentifier = ContentStreamIdentifier::create();
                    $similarlyNamedWorkspaces = $this->workspaceFinder->findByPrefix(
                        $workspaceName->toContentRepositoryWorkspaceName()
                    );
                    if (!empty($similarlyNamedWorkspaces)) {
                        $workspaceName = $workspaceName->increment($similarlyNamedWorkspaces);
                    }

                    $this->workspaceCommandHandler->handleCreateWorkspace(
                        new CreateWorkspace(
                            $workspaceName->toContentRepositoryWorkspaceName(),
                            $baseWorkspace->getWorkspaceName(),
                            new WorkspaceTitle((string) $user->getName()),
                            new WorkspaceDescription(''),
                            $userIdentifier,
                            $editorsNewContentStreamIdentifier,
                            $userIdentifier
                        )
                    )->blockUntilProjectionsAreUpToDate();
                } else {
                    $this->workspaceCommandHandler->handleRebaseWorkspace(
                        RebaseWorkspace::create(
                            $workspace->getWorkspaceName(),
                            $userIdentifier
                        )
                    )->blockUntilProjectionsAreUpToDate();
                }
            }
        }
    }
}
