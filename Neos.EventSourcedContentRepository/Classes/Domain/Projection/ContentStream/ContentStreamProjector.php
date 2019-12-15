<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Projection\ContentStream;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\ORM\EntityManagerInterface;
use Neos\Cache\Frontend\VariableFrontend;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Event\ContentStreamWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Event\ContentStreamWasForked;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Event\ContentStreamWasRemoved;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\RootWorkspaceWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceRebaseFailed;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasCreated;
use Neos\EventSourcedContentRepository\Domain\Context\Workspace\Event\WorkspaceWasRebased;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventListener\AfterInvokeInterface;
use Neos\EventSourcing\EventStore\EventEnvelope;
use Neos\Flow\Annotations as Flow;
use Neos\EventSourcing\Projection\ProjectorInterface;

/**
 * Workspace Projector
 * @Flow\Scope("singleton")
 */
class ContentStreamProjector implements ProjectorInterface, AfterInvokeInterface
{
    private const TABLE_NAME = 'neos_contentrepository_projection_contentstream_v1';

    /**
     * @var \Doctrine\DBAL\Connection
     */
    private $dbal;

    /**
     * @Flow\Inject
     * @var VariableFrontend
     */
    protected $processedEventsCache;

    /**
     * @var bool
     */
    protected $assumeProjectorRunsSynchronously = false;

    public function injectEntityManager(EntityManagerInterface $entityManager): void
    {
        $this->dbal = $entityManager->getConnection();
    }

    /**
     * @internal
     */
    public function assumeProjectorRunsSynchronously()
    {
        $this->assumeProjectorRunsSynchronously = true;
    }

    public function isEmpty(): bool
    {
        return false;
    }

    public function whenContentStreamWasCreated(ContentStreamWasCreated $event)
    {
        $this->dbal->insert(self::TABLE_NAME, [
            'contentStreamIdentifier' => $event->getContentStreamIdentifier(),
            'state' => ContentStreamFinder::STATE_CREATED,
        ]);
    }

    public function whenRootWorkspaceWasCreated(RootWorkspaceWasCreated $event)
    {
        // the content stream is in use now
        $this->dbal->update(self::TABLE_NAME, [
            'state' => ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE,
        ], [
            'contentStreamIdentifier' => $event->getCurrentContentStreamIdentifier()
        ]);
    }

    public function whenWorkspaceWasCreated(WorkspaceWasCreated $event)
    {
        // the content stream is in use now
        $this->dbal->update(self::TABLE_NAME, [
            'state' => ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE,
        ], [
            'contentStreamIdentifier' => $event->getCurrentContentStreamIdentifier()
        ]);
    }

    public function whenContentStreamWasForked(ContentStreamWasForked $event)
    {
        $this->dbal->insert(self::TABLE_NAME, [
            'contentStreamIdentifier' => $event->getContentStreamIdentifier(),
            'sourceContentStreamIdentifier' => $event->getSourceContentStreamIdentifier(),
            'state' => ContentStreamFinder::STATE_REBASING,
        ]);
    }


    public function whenWorkspaceWasRebased(WorkspaceWasRebased $event)
    {
        // the new content stream is in use now
        $this->dbal->update(self::TABLE_NAME, [
            'state' => ContentStreamFinder::STATE_IN_USE_BY_WORKSPACE,
        ], [
            'contentStreamIdentifier' => $event->getCurrentContentStreamIdentifier()
        ]);

        // the previous content stream is no longer in use
        $this->dbal->update(self::TABLE_NAME, [
            'state' => ContentStreamFinder::STATE_NO_LONGER_IN_USE,
        ], [
            'contentStreamIdentifier' => $event->getPreviousContentStreamIdentifier()
        ]);
    }

    public function whenWorkspaceRebaseFailed(WorkspaceRebaseFailed $event)
    {
        $this->dbal->update(self::TABLE_NAME, [
            'state' => ContentStreamFinder::STATE_REBASE_ERROR,
        ], [
            'contentStreamIdentifier' => $event->getTargetContentStreamIdentifier()
        ]);
    }

    public function whenContentStreamWasRemoved(ContentStreamWasRemoved $event)
    {
        $this->dbal->update(self::TABLE_NAME, [
            'removed' => true
        ], [
            'contentStreamIdentifier' => $event->getContentStreamIdentifier()
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
        if ($this->assumeProjectorRunsSynchronously === true) {
            // if we run synchronously during an import, we don't need the processed events cache
            return;
        }
        $this->processedEventsCache->set(md5($eventEnvelope->getRawEvent()->getIdentifier()), true);
    }

    public function hasProcessed(DomainEvents $events): bool
    {
        if ($this->assumeProjectorRunsSynchronously === true) {
            // if we run synchronously during an import, we *know* that events have been processed already.
            return true;
        }
        foreach ($events as $event) {
            if (!$event instanceof DecoratedEvent) {
                throw new \RuntimeException(sprintf('The CommandResult contains an event "%s" that is no DecoratedEvent', get_class($event)), 1550314769);
            }
            if (!$this->processedEventsCache->has(md5($event->getIdentifier()))) {
                return false;
            }
        }
        return true;
    }

}
