<?php

namespace Neos\ContentRepository\Domain\Projection\Workspace;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Context\ContentStream\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Domain\Context\Workspace\Event\RootWorkspaceWasCreated;
use Neos\ContentRepository\Domain\Context\Workspace\Event\WorkspaceWasCreated;
use Neos\EventSourcing\Projection\Doctrine\AbstractDoctrineProjector;

/**
 * Workspace Projector
 */
final class WorkspaceProjector extends AbstractDoctrineProjector
{
    /**
     * @param WorkspaceWasCreated $event
     */
    public function whenWorkspaceWasCreated(WorkspaceWasCreated $event)
    {
        $workspace = new Workspace();
        $workspace->workspaceName = $event->getWorkspaceName();
        $workspace->baseWorkspaceName = $event->getBaseWorkspaceName();
        $workspace->workspaceTitle = $event->getWorkspaceTitle();
        $workspace->workspaceDescription = $event->getWorkspaceDescription();
        $workspace->workspaceOwner = $event->getWorkspaceOwner();

        $this->add($workspace);
    }
    /**
     * @param RootWorkspaceWasCreated $event
     */
    public function whenRootWorkspaceWasCreated(RootWorkspaceWasCreated $event)
    {
        $workspace = new Workspace();
        $workspace->workspaceName = $event->getWorkspaceName();
        $workspace->workspaceTitle = $event->getWorkspaceTitle();
        $workspace->workspaceDescription = $event->getWorkspaceDescription();

        $this->add($workspace);
    }

    public function whenContentStreamWasCreated(ContentStreamWasCreated $event)
    {
        // TODO: we need to change this code as soon as we have two content streams for a workspace, i.e. want to implement rebase.
        /* @var $workspace Workspace */
        $workspace = $this->get($event->getWorkspaceName());
        if ($workspace !== null) {
            $workspace->_setCurrentContentStreamIdentifier($event->getContentStreamIdentifier());
            $this->update($workspace);
        }
    }
}
