<?php
declare(strict_types=1);
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

use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\UserIdentifier;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceDescription;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\WorkspaceTitle;
use Neos\Flow\Annotations as Flow;

/**
 * Command to create a root workspace
 *
 * @Flow\Proxy(false)
 */
final class CreateRootWorkspace
{
    private WorkspaceName $workspaceName;

    private WorkspaceTitle $workspaceTitle;

    private WorkspaceDescription $workspaceDescription;

    private UserIdentifier $initiatingUserIdentifier;

    private ContentStreamIdentifier $newContentStreamIdentifier;

    public function __construct(
        WorkspaceName $workspaceName,
        WorkspaceTitle $workspaceTitle,
        WorkspaceDescription $workspaceDescription,
        UserIdentifier $initiatingUserIdentifier,
        ContentStreamIdentifier $newContentStreamIdentifier
    ) {
        $this->workspaceName = $workspaceName;
        $this->workspaceTitle = $workspaceTitle;
        $this->workspaceDescription = $workspaceDescription;
        $this->initiatingUserIdentifier = $initiatingUserIdentifier;
        $this->newContentStreamIdentifier = $newContentStreamIdentifier;
    }

    public static function fromArray(array $array): self
    {
        return new static(
            new WorkspaceName($array['workspaceName']),
            new WorkspaceTitle($array['workspaceTitle']),
            new WorkspaceDescription($array['workspaceDescription']),
            UserIdentifier::fromString($array['initiatingUserIdentifier']),
            ContentStreamIdentifier::fromString($array['newContentStreamIdentifier'])
        );
    }

    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    public function getWorkspaceTitle(): WorkspaceTitle
    {
        return $this->workspaceTitle;
    }

    public function getWorkspaceDescription(): WorkspaceDescription
    {
        return $this->workspaceDescription;
    }

    public function getInitiatingUserIdentifier(): UserIdentifier
    {
        return $this->initiatingUserIdentifier;
    }

    public function getNewContentStreamIdentifier(): ContentStreamIdentifier
    {
        return $this->newContentStreamIdentifier;
    }
}
