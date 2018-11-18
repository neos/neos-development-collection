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
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceDescription;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceTitle;

/**
 * Create a root workspace
 */
final class CreateRootWorkspace
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
     * @var ContentStreamIdentifier
     */
    private $contentStreamIdentifier;

    /**
     * @var NodeIdentifier
     */
    private $rootNodeIdentifier;

    /**
     * @var NodeTypeName
     */
    private $rootNodeTypeName;

    /**
     * CreateRootWorkspace constructor.
     * @param WorkspaceName $workspaceName
     * @param WorkspaceTitle $workspaceTitle
     * @param WorkspaceDescription $workspaceDescription
     * @param UserIdentifier $initiatingUserIdentifier
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @param NodeIdentifier $rootNodeIdentifier
     * @param NodeTypeName $rootNodeTypeName
     */
    public function __construct(WorkspaceName $workspaceName, WorkspaceTitle $workspaceTitle, WorkspaceDescription $workspaceDescription, UserIdentifier $initiatingUserIdentifier, ContentStreamIdentifier $contentStreamIdentifier, NodeIdentifier $rootNodeIdentifier, NodeTypeName $rootNodeTypeName)
    {
        $this->workspaceName = $workspaceName;
        $this->workspaceTitle = $workspaceTitle;
        $this->workspaceDescription = $workspaceDescription;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->rootNodeIdentifier = $rootNodeIdentifier;
        $this->rootNodeTypeName = $rootNodeTypeName;
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

    /**
     * @return ContentStreamIdentifier
     */
    public function getContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->contentStreamIdentifier;
    }

    /**
     * @return NodeIdentifier
     */
    public function getRootNodeIdentifier(): NodeIdentifier
    {
        return $this->rootNodeIdentifier;
    }

    /**
     * @return NodeTypeName
     */
    public function getRootNodeTypeName(): NodeTypeName
    {
        return $this->rootNodeTypeName;
    }
}
