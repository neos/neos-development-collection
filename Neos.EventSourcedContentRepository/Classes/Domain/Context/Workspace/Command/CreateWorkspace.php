<?php
namespace Neos\EventSourcedContentRepository\Domain\Context\Workspace\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\ValueObject\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceDescription;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceTitle;

/**
 * Create a new workspace, based on an existing baseWorkspace
 */
final class CreateWorkspace
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
     * the content stream identifier for the content stream which is created together with the to-be-created workspace
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var UserIdentifier
     */
    private $workspaceOwner;

    /**
     * CreateWorkspace constructor.
     *
     * @param WorkspaceName $workspaceName
     * @param WorkspaceName $baseWorkspaceName
     * @param WorkspaceTitle $workspaceTitle
     * @param WorkspaceDescription $workspaceDescription
     * @param UserIdentifier $initiatingUserIdentifier
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param UserIdentifier $workspaceOwner
     */
    public function __construct(WorkspaceName $workspaceName, WorkspaceName $baseWorkspaceName, WorkspaceTitle $workspaceTitle, WorkspaceDescription $workspaceDescription, UserIdentifier $initiatingUserIdentifier, ContentStreamIdentifier $contentStreamIdentifier = null, UserIdentifier $workspaceOwner = null)
    {
        $this->workspaceName = $workspaceName;
        $this->baseWorkspaceName = $baseWorkspaceName;
        $this->workspaceTitle = $workspaceTitle;
        $this->workspaceDescription = $workspaceDescription;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
        $this->contentStreamIdentifier = $contentStreamIdentifier ?: new ContentStreamIdentifier();
        ;
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
     * @return WorkspaceName
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
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return UserIdentifier|null
     */
    public function getWorkspaceOwner()
    {
        return $this->workspaceOwner;
    }
}
