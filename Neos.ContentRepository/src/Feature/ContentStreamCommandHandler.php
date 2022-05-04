<?php
declare(strict_types=1);
namespace Neos\ContentRepository\Feature;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\ContentRepository\Feature\ContentStreamEventStreamName;
use Neos\ContentRepository\Feature\ContentStreamRepository;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Feature\Common\Exception\ContentStreamAlreadyExists;
use Neos\ContentRepository\Feature\Common\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Infrastructure\Projection\CommandResult;
use Neos\ContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
use Neos\EventSourcing\Event\DecoratedEvent;
use Neos\EventSourcing\Event\DomainEvents;
use Neos\EventSourcing\EventStore\EventStore;
use Ramsey\Uuid\Uuid;

/**
 * INTERNALS. Only to be used from WorkspaceCommandHandler!!!
 *
 * @Flow\Scope("singleton")
 * ContentStreamCommandHandler
 */
final class ContentStreamCommandHandler
{
    private ContentStreamRepository $contentStreamRepository;

    private EventStore $eventStore;

    private ReadSideMemoryCacheManager $readSideMemoryCacheManager;

    private RuntimeBlocker $runtimeBlocker;

    public function __construct(
        ContentStreamRepository $contentStreamRepository,
        EventStore $eventStore,
        ReadSideMemoryCacheManager $readSideMemoryCacheManager,
        RuntimeBlocker $runtimeBlocker
    ) {
        $this->contentStreamRepository = $contentStreamRepository;
        $this->eventStore = $eventStore;
        $this->readSideMemoryCacheManager = $readSideMemoryCacheManager;
        $this->runtimeBlocker = $runtimeBlocker;
    }

    /**
     * @param \Neos\ContentRepository\Feature\ContentStreamCreation\Command\CreateContentStream $command
     * @return CommandResult
     * @throws ContentStreamAlreadyExists
     */
    public function handleCreateContentStream(
        \Neos\ContentRepository\Feature\ContentStreamCreation\Command\CreateContentStream $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        $this->requireContentStreamToNotExistYet($command->getContentStreamIdentifier());
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($command->getContentStreamIdentifier())
            ->getEventStreamName();
        $events = DomainEvents::withSingleEvent(
            DecoratedEvent::addIdentifier(
                new \Neos\ContentRepository\Feature\ContentStreamCreation\Event\ContentStreamWasCreated(
                    $command->getContentStreamIdentifier(),
                    $command->getInitiatingUserIdentifier()
                ),
                Uuid::uuid4()->toString()
            )
        );
        $this->eventStore->commit($streamName, $events);

        return CommandResult::fromPublishedEvents($events, $this->runtimeBlocker);
    }

    /**
     * @param \Neos\ContentRepository\Feature\ContentStreamForking\Command\ForkContentStream $command
     * @return CommandResult
     * @throws ContentStreamAlreadyExists
     * @throws ContentStreamDoesNotExistYet
     */
    public function handleForkContentStream(
        \Neos\ContentRepository\Feature\ContentStreamForking\Command\ForkContentStream $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        $this->requireContentStreamToExist($command->getSourceContentStreamIdentifier());
        $this->requireContentStreamToNotExistYet($command->getContentStreamIdentifier());

        $sourceContentStream = $this->contentStreamRepository->findContentStream(
            $command->getSourceContentStreamIdentifier()
        );
        $sourceContentStreamVersion = $sourceContentStream !== null ? $sourceContentStream->getVersion() : -1;

        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($command->getContentStreamIdentifier())
            ->getEventStreamName();

        $events = DomainEvents::withSingleEvent(
            DecoratedEvent::addIdentifier(
                new \Neos\ContentRepository\Feature\ContentStreamForking\Event\ContentStreamWasForked(
                    $command->getContentStreamIdentifier(),
                    $command->getSourceContentStreamIdentifier(),
                    $sourceContentStreamVersion,
                    $command->getInitiatingUserIdentifier()
                ),
                Uuid::uuid4()->toString()
            )
        );
        $this->eventStore->commit($streamName, $events);

        return CommandResult::fromPublishedEvents($events, $this->runtimeBlocker);
    }

    public function handleRemoveContentStream(
        \Neos\ContentRepository\Feature\ContentStreamRemoval\Command\RemoveContentStream $command): CommandResult
    {
        $this->requireContentStreamToExist($command->getContentStreamIdentifier());

        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
            $command->getContentStreamIdentifier()
        )->getEventStreamName();

        $events = DomainEvents::withSingleEvent(
            DecoratedEvent::addIdentifier(
                new \Neos\ContentRepository\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved(
                    $command->getContentStreamIdentifier(),
                    $command->getInitiatingUserIdentifier()
                ),
                Uuid::uuid4()->toString()
            )
        );
        $this->eventStore->commit($streamName, $events);

        return CommandResult::fromPublishedEvents($events, $this->runtimeBlocker);
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @throws ContentStreamAlreadyExists
     */
    protected function requireContentStreamToNotExistYet(ContentStreamIdentifier $contentStreamIdentifier): void
    {
        if ($this->contentStreamRepository->findContentStream($contentStreamIdentifier)) {
            throw new ContentStreamAlreadyExists(
                'Content stream "' . $contentStreamIdentifier . '" already exists.',
                1521386345
            );
        }
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @throws ContentStreamDoesNotExistYet
     */
    protected function requireContentStreamToExist(ContentStreamIdentifier $contentStreamIdentifier): void
    {
        if (!$this->contentStreamRepository->findContentStream($contentStreamIdentifier)) {
            throw new ContentStreamDoesNotExistYet(
                'Content stream "' . $contentStreamIdentifier . '" does not exist yet.',
                1521386692
            );
        }
    }
}
