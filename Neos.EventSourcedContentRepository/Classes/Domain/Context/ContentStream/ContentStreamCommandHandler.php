<?php
declare(strict_types=1);
namespace Neos\EventSourcedContentRepository\Domain\Context\ContentStream;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\EventSourcedContentRepository\Infrastructure\Projection\RuntimeBlocker;
use Neos\Flow\Annotations as Flow;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Exception\ContentStreamAlreadyExists;
use Neos\EventSourcedContentRepository\Domain\Context\ContentStream\Exception\ContentStreamDoesNotExistYet;
use Neos\EventSourcedContentRepository\Domain\CommandResult;
use Neos\EventSourcedContentRepository\Service\Infrastructure\ReadSideMemoryCacheManager;
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
     * @param Command\CreateContentStream $command
     * @return CommandResult
     * @throws ContentStreamAlreadyExists
     */
    public function handleCreateContentStream(Command\CreateContentStream $command): CommandResult
    {
        $this->readSideMemoryCacheManager->disableCache();

        $this->requireContentStreamToNotExistYet($command->getContentStreamIdentifier());
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($command->getContentStreamIdentifier())
            ->getEventStreamName();
        $events = DomainEvents::withSingleEvent(
            DecoratedEvent::addIdentifier(
                new Event\ContentStreamWasCreated(
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
     * @param Command\ForkContentStream $command
     * @return CommandResult
     * @throws ContentStreamAlreadyExists
     * @throws ContentStreamDoesNotExistYet
     */
    public function handleForkContentStream(Command\ForkContentStream $command): CommandResult
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
                new Event\ContentStreamWasForked(
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

    public function handleRemoveContentStream(Command\RemoveContentStream $command): CommandResult
    {
        $this->requireContentStreamToExist($command->getContentStreamIdentifier());

        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
            $command->getContentStreamIdentifier()
        )->getEventStreamName();

        $events = DomainEvents::withSingleEvent(
            DecoratedEvent::addIdentifier(
                new Event\ContentStreamWasRemoved(
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
