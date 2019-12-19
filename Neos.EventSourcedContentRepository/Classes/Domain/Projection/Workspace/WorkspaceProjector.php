<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Projection\Workspace;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\RootWorkspaceWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasRebased;
use Neos\EventSourcedContentRepository\Infrastructure\Projection\AbstractProcessedEventsAwareProjector;

class WorkspaceProjector extends AbstractProcessedEventsAwareProjector
{
    private const TABLE_NAME = 'neos_contentrepository_projection_workspace_v1';

    /**
     * @param WorkspaceWasCreated $event
     */
    public function whenWorkspaceWasCreated(WorkspaceWasCreated $event)
    {
        $this->getDatabaseConnection()->insert(self::TABLE_NAME, [
            'workspaceName' => $event->getWorkspaceName(),
            'baseWorkspaceName' => $event->getBaseWorkspaceName(),
            'workspaceTitle' => $event->getWorkspaceTitle(),
            'workspaceDescription' => $event->getWorkspaceDescription(),
            'workspaceOwner' => $event->getWorkspaceOwner(),
            'currentContentStreamIdentifier' => $event->getCurrentContentStreamIdentifier()
        ]);
    }

    /**
     * @param RootWorkspaceWasCreated $event
     */
    public function whenRootWorkspaceWasCreated(RootWorkspaceWasCreated $event)
    {
        $this->getDatabaseConnection()->insert(self::TABLE_NAME, [
            'workspaceName' => $event->getWorkspaceName(),
            'workspaceTitle' => $event->getWorkspaceTitle(),
            'workspaceDescription' => $event->getWorkspaceDescription(),
            'currentContentStreamIdentifier' => $event->getCurrentContentStreamIdentifier()
        ]);
    }

    public function whenWorkspaceWasRebased(WorkspaceWasRebased $event)
    {
        $this->getDatabaseConnection()->update(self::TABLE_NAME, [
            'currentContentStreamIdentifier' => $event->getCurrentContentStreamIdentifier()
        ], [
            'workspaceName' => $event->getWorkspaceName()
        ]);
    }

    public function reset(): void
    {
        parent::reset();
        $this->getDatabaseConnection()->exec('TRUNCATE ' . self::TABLE_NAME);
    }
}
