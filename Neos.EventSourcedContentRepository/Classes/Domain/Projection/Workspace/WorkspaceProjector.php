<?php
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
use Neos\Flow\Annotations as Flow;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\RootWorkspaceWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasCreated;
use Doctrine\Common\Persistence\ObjectManager as DoctrineObjectManager;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasRebased;
use Neos\EventSourcing\Projection\ProjectorInterface;

/**
 * Workspace Projector
 */
final class WorkspaceProjector implements ProjectorInterface
{
    private const TABLE_NAME = 'neos_contentrepository_projection_workspace_v1';

    /**
     * @Flow\Inject
     * @var WorkspaceFinder
     */
    protected $workspaceFinder;

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $dbal;

    public function injectEntityManager(DoctrineObjectManager $entityManager): void
    {
        if ($entityManager instanceof DoctrineEntityManager) {
            $this->dbal = $entityManager->getConnection();
        }
    }

    public function isEmpty(): bool
    {
        return false;
    }

    /**
     * @param WorkspaceWasCreated $event
     */
    public function whenWorkspaceWasCreated(WorkspaceWasCreated $event)
    {
        $this->dbal->insert(self::TABLE_NAME, [
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
        $this->dbal->insert(self::TABLE_NAME, [
            'workspaceName' => $event->getWorkspaceName(),
            'workspaceTitle' => $event->getWorkspaceTitle(),
            'workspaceDescription' => $event->getWorkspaceDescription(),
            'currentContentStreamIdentifier' => $event->getCurrentContentStreamIdentifier()
        ]);
    }

    public function whenWorkspaceWasRebased(WorkspaceWasRebased $event)
    {
        $this->dbal->update(self::TABLE_NAME, [
            'currentContentStreamIdentifier' => $event->getCurrentContentStreamIdentifier()
        ], [
            'workspaceName' => $event->getWorkspaceName()
        ]);

        // TODO: HACK to update in-memory projection(!!!!!!) nasty!!!
        $this->workspaceFinder->findOneByName($event->getWorkspaceName())->currentContentStreamIdentifier = $event->getCurrentContentStreamIdentifier();
    }

    public function reset(): void
    {
        $this->dbal->transactional(function () {
            $this->dbal->exec('TRUNCATE ' . self::TABLE_NAME);
        });
    }
}
