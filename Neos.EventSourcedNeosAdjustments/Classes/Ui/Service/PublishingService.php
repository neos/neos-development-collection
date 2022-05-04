<?php
declare(strict_types=1);
namespace Neos\EventSourcedNeosAdjustments\Ui\Service;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Feature\WorkspaceCommandHandler;
use Neos\ContentRepository\SharedModel\User\UserIdentifier;
use Neos\ContentRepository\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Neos\Domain\Model\User;
use Neos\Neos\Service\UserService;

/**
 * A generic ContentRepository Publishing Service
 *
 * @api
 * @Flow\Scope("singleton")
 */
class PublishingService
{
    /**
     * @Flow\Inject
     * @var PersistenceManagerInterface
     */
    protected $persistenceManager;

    /**
     * @Flow\Inject
     * @var UserService
     */
    protected $userService;

    /**
     * @Flow\Inject
     * @var WorkspaceCommandHandler
     */
    protected $workspaceCommandHandler;

    public function publishWorkspace(WorkspaceName $workspaceName): void
    {
        /** @var User $backendUser */
        $backendUser = $this->userService->getBackendUser();
        $userIdentifier = UserIdentifier::fromString(
            $this->persistenceManager->getIdentifierByObject($backendUser)
        );

        // TODO: only rebase if necessary!
        $this->workspaceCommandHandler->handleRebaseWorkspace(
            RebaseWorkspace::create(
                $workspaceName,
                $userIdentifier
            )
        )->blockUntilProjectionsAreUpToDate();

        $this->workspaceCommandHandler->handlePublishWorkspace(
            new PublishWorkspace(
                $workspaceName,
                $userIdentifier
            )
        );
    }
}
