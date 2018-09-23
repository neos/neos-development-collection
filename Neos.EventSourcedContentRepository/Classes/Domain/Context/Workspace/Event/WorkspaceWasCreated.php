<?php
namespace Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceDescription;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceTitle;
use Neos\EventSourcing\Event\EventInterface;

/**
 * WorkspaceWasCreated
 */
class WorkspaceWasCreated implements EventInterface
{
    /**
     * @var WorkspaceName
     */
    private $workspaceName;

    /**
     * @var WorkspaceName
     */
    private $baseWorkspaceName;

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
     * @var ContentStreamIdentifier
     */
    private $currentContentStreamIdentifier;

    /**
     * @var UserIdentifier
     */
    private $workspaceOwner;

    /**
     * WorkspaceWasCreated constructor.
     * @param WorkspaceName $workspaceName
     * @param WorkspaceName $baseWorkspaceName
     * @param WorkspaceTitle $workspaceTitle
     * @param WorkspaceDescription $workspaceDescription
     * @param UserIdentifier $initiatingUserIdentifier
     * @param ContentStreamIdentifier $currentContentStreamIdentifier
     * @param UserIdentifier $workspaceOwner
     */
    public function __construct(WorkspaceName $workspaceName, WorkspaceName $baseWorkspaceName, WorkspaceTitle $workspaceTitle, WorkspaceDescription $workspaceDescription, UserIdentifier $initiatingUserIdentifier, ContentStreamIdentifier $currentContentStreamIdentifier, UserIdentifier $workspaceOwner = null)
    {
        $this->workspaceName = $workspaceName;
        $this->baseWorkspaceName = $baseWorkspaceName;
        $this->workspaceTitle = $workspaceTitle;
        $this->workspaceDescription = $workspaceDescription;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
        $this->currentContentStreamIdentifier = $currentContentStreamIdentifier;
        $this->workspaceOwner = $workspaceOwner;
    }

    /**
     * @return WorkspaceName
     */
    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    /**
     * @return WorkspaceName|null
     */
    public function getBaseWorkspaceName()
    {
        return $this->baseWorkspaceName;
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

    /**
     * @return ContentStreamIdentifier
     */
    public function getCurrentContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->currentContentStreamIdentifier;
    }

    /**
     * @return UserIdentifier|null
     */
    public function getWorkspaceOwner() : ?UserIdentifier
    {
        return $this->workspaceOwner;
    }
}
