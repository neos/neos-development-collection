<?php
namespace Neos\ContentRepository\Domain\Context\Workspace\Event;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\ContentRepository\Domain\ValueObject\WorkspaceDescription;
use Neos\ContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\ContentRepository\Domain\ValueObject\WorkspaceTitle;
use Neos\EventSourcing\Event\EventInterface;

/**
 * LiveWorkspaceWasCreated
 */
class RootWorkspaceWasCreated implements EventInterface
{
    /**
     * @var WorkspaceName
     */
    private $workspaceName;

    /**
     * @var WorkspaceTitle
     */
    private $workspaceTitle;

    /**
     * @var WorkspaceDescription
     */
    private $workspaceDescription;

    /**
     * @var UserIdentifier
     */
    private $initiatingUserIdentifier;

    /**
     * RootWorkspaceWasCreated constructor.
     *
     * @param WorkspaceName $workspaceName
     * @param WorkspaceTitle $workspaceTitle
     * @param WorkspaceDescription $workspaceDescription
     * @param UserIdentifier $initiatingUserIdentifier
     */
    public function __construct(WorkspaceName $workspaceName, WorkspaceTitle $workspaceTitle, WorkspaceDescription $workspaceDescription, UserIdentifier $initiatingUserIdentifier)
    {
        $this->workspaceName = $workspaceName;
        $this->workspaceTitle = $workspaceTitle;
        $this->workspaceDescription = $workspaceDescription;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
    }

    /**
     * @return WorkspaceName
     */
    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    /**
     * @return WorkspaceTitle
     */
    public function getWorkspaceTitle(): WorkspaceTitle
    {
        return $this->workspaceTitle;
    }

    /**
     * @return WorkspaceDescription
     */
    public function getWorkspaceDescription(): WorkspaceDescription
    {
        return $this->workspaceDescription;
    }

    /**
     * @return UserIdentifier
     */
    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }
}
