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

namespace Neos\Neos\Service;

use Neos\ContentRepository\Core\Feature\WorkspaceCreation\Command\CreateWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Core\Projection\Workspace\Workspace;
use Neos\ContentRepository\Core\Projection\Workspace\WorkspaceStatus;
use Neos\ContentRepository\Core\SharedModel\User\UserId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceDescription;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceTitle;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\ContentRepositoryRegistry\Factory\ProjectionCatchUpTrigger\CatchUpTriggerWithSynchronousOption;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Core\Bootstrap;
use Neos\Flow\Http\HttpRequestHandlerInterface;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\Security\Authentication;
use Neos\Flow\Security\Policy\PolicyService;
use Neos\Flow\Security\Policy\Role;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Domain\Service\WorkspaceNameBuilder;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionFailedException;
use Neos\Neos\FrontendRouting\SiteDetection\SiteDetectionResult;
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
     * @var Bootstrap
     */
    protected $bootstrap;

    /**
     * @Flow\Inject
     * @var ContentRepositoryRegistry
     */
    protected $contentRepositoryRegistry;

    /**
     * This method is called whenever a login happens (AuthenticationProviderManager::class, 'authenticatedToken'),
     * using Signal/Slot
     *
     * @param Authentication\TokenInterface $token
     * @throws \Exception
     * @throws \Neos\ContentRepository\Core\Feature\WorkspaceCreation\Exception\BaseWorkspaceDoesNotExist
     * @throws \Neos\ContentRepository\Core\Feature\WorkspaceCreation\Exception\WorkspaceAlreadyExists
     * @throws \Neos\ContentRepository\Core\SharedModel\Exception\WorkspaceDoesNotExist
     * @throws \Neos\Flow\Persistence\Exception\InvalidQueryException
     */
    public function relayEditorAuthentication(Authentication\TokenInterface $token): void
    {
        $requestHandler = $this->bootstrap->getActiveRequestHandler();
        if (!$requestHandler instanceof HttpRequestHandlerInterface) {
            // we might be in testing context
            return;
        }
        try {
            $siteDetectionResult = SiteDetectionResult::fromRequest($requestHandler->getHttpRequest());
        } catch (SiteDetectionFailedException) {
            return;
        }
        $contentRepository = $this->contentRepositoryRegistry->get($siteDetectionResult->contentRepositoryId);

        $isEditor = false;
        foreach ($token->getAccount()->getRoles() as $role) {
            /** @var Role $role */
            if (isset($role->getAllParentRoles()['Neos.Neos:AbstractEditor'])) {
                $isEditor = true;
                break;
            }
        }
        if (!$isEditor) {
            return;
        }
        $user = $this->partyService->getAssignedPartyOfAccount($token->getAccount());
        if (!$user instanceof User) {
            return;
        }
        $workspaceName = WorkspaceNameBuilder::fromAccountIdentifier(
            $token->getAccount()->getAccountIdentifier()
        );
        $workspace = $contentRepository->getWorkspaceFinder()->findOneByName($workspaceName);
        if ($workspace !== null) {
            if ($workspace->status !== WorkspaceStatus::OUTDATED) {
                // the workspace is either okay or in conflict, which would prevent the editor from logging in
                return;
            }

            CatchUpTriggerWithSynchronousOption::synchronously(fn() => $contentRepository->handle(
                RebaseWorkspace::create(
                    $workspace->workspaceName,
                )
            )->block());
            return;
        }

        $baseWorkspace = $contentRepository->getWorkspaceFinder()->findOneByName(WorkspaceName::forLive());
        if (!$baseWorkspace) {
            return;
        }
        $editorsNewContentStreamId = ContentStreamId::create();
        $contentRepository->handle(
            CreateWorkspace::create(
                $workspaceName,
                $baseWorkspace->workspaceName,
                new WorkspaceTitle((string) $user->getName()),
                new WorkspaceDescription(''),
                $editorsNewContentStreamId,
                UserId::fromString($this->persistenceManager->getIdentifierByObject($user))
            )
        )->block();
    }
}
