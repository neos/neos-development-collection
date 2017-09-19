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

use Neos\ContentRepository\Domain\Context\Workspace\Event\WorkspaceHasBeenCreated;
use Neos\EventSourcing\Projection\Doctrine\AbstractDoctrineProjector;

/**
 * Workspace Projector
 */
final class WorkspaceProjector extends AbstractDoctrineProjector
{
    /**
     * @param WorkspaceHasBeenCreated $event
     */
    public function whenWorkspaceHasBeenCreated(WorkspaceHasBeenCreated $event)
    {
        $workspace = new Workspace();
        $workspace->workspaceName = $event->getWorkspaceName();
        $workspace->baseWorkspaceName = $event->getBaseWorkspaceName();
        $workspace->workspaceTitle = $event->getWorkspaceTitle();
        $workspace->workspaceDescription = $event->getWorkspaceDescription();
        $workspace->workspaceOwner = $event->getWorkspaceOwner();

        $this->add($workspace);
    }
}
