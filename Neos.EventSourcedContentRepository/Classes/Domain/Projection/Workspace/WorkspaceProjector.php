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

use Neos\Flow\Annotations as Flow;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\RootWorkspaceWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasCreated;
use Doctrine\Common\Persistence\ObjectManager as DoctrineObjectManager;
use Doctrine\ORM\EntityManager as DoctrineEntityManager;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasRebased;
use Neos\EventSourcing\Event\Decorator\DomainEventWithIdentifierInterface;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventListener\AfterInvokeInterface;
use Neos\EventSourcing\EventStore\EventEnvelope;
use Neos\EventSourcing\Projection\ProjectorInterface;

/**
 * Workspace Projector
 * @Flow\Scope("singleton")
 */
class WorkspaceProjector implements ProjectorInterface, AfterInvokeInterface
{
    private const TABLE_NAME = 'neos_contentrepository_projection_workspace_v1';

    /**
     * @Flow\Inject
     * @var VariableFrontend
     */
    protected $processedEventsCache;

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
    }

    public function reset(): void
    {
        $this->dbal->transactional(function () {
            $this->dbal->exec('TRUNCATE ' . self::TABLE_NAME);
        });
    }

    /**
     * Called after a listener method is invoked
     *
     * @param EventEnvelope $eventEnvelope
     * @return void
     */
    public function afterInvoke(EventEnvelope $eventEnvelope): void
    {
        $this->processedEventsCache->set(md5($eventEnvelope->getRawEvent()->getIdentifier()), true);
    }

    public function hasProcessed(DomainEvents $events): bool
    {
        foreach ($events as $event) {
            if (!$event instanceof DomainEventWithIdentifierInterface) {
                throw new \RuntimeException(sprintf('The CommandResult contains an event "%s" that does not implement the %s interface', get_class($event), DomainEventWithIdentifierInterface::class), 1550314769);
            }
            if (!$this->processedEventsCache->has(md5($event->getIdentifier()))) {
                return false;
            }
        }
        return true;
    }
}
