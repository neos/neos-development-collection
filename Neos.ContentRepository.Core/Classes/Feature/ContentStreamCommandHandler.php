<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature;

use Neos\ContentRepository\Core\CommandHandler\CommandHandlerInterface;
use Neos\ContentRepository\Core\CommandHandler\CommandInterface;
use Neos\ContentRepository\Core\CommandHandlingDependencies;
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Command\CloseContentStream;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Command\ReopenContentStream;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasClosed;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Event\ContentStreamWasReopened;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Command\CreateContentStream;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Command\ForkContentStream;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Command\RemoveContentStream;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamAlreadyExists;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamIsClosed;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamIsNotClosed;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamState;
use Neos\EventStore\Model\EventStream\ExpectedVersion;

/**
 * INTERNALS. Only to be used from WorkspaceCommandHandler!!!
 *
 * @internal from userland, you'll use ContentRepository::handle to dispatch commands
 */
final class ContentStreamCommandHandler implements CommandHandlerInterface
{
    public function canHandle(CommandInterface $command): bool
    {
        return method_exists($this, 'handle' . (new \ReflectionClass($command))->getShortName());
    }

    public function handle(CommandInterface $command, CommandHandlingDependencies $commandHandlingDependencies): EventsToPublish
    {
        return match ($command::class) {
            CreateContentStream::class => $this->handleCreateContentStream($command),
            CloseContentStream::class => $this->handleCloseContentStream($command, $commandHandlingDependencies),
            ReopenContentStream::class => $this->handleReopenContentStream($command, $commandHandlingDependencies),
            ForkContentStream::class => $this->handleForkContentStream($command, $commandHandlingDependencies),
            RemoveContentStream::class => $this->handleRemoveContentStream($command, $commandHandlingDependencies),
            default => throw new \DomainException('Cannot handle commands of class ' . get_class($command), 1710408206),
        };
    }

    /**
     * @throws ContentStreamAlreadyExists
     */
    private function handleCreateContentStream(
        CreateContentStream $command
    ): EventsToPublish {
        $streamName = ContentStreamEventStreamName::fromContentStreamId($command->contentStreamId)
            ->getEventStreamName();

        return new EventsToPublish(
            $streamName,
            Events::with(
                new ContentStreamWasCreated(
                    $command->contentStreamId,
                )
            ),
            ExpectedVersion::NO_STREAM()
        );
    }

    private function handleCloseContentStream(
        CloseContentStream $command,
        CommandHandlingDependencies $commandHandlingDependencies,
    ): EventsToPublish {
        $this->requireContentStreamToExist($command->contentStreamId, $commandHandlingDependencies);
        $expectedVersion = $this->getExpectedVersionOfContentStream($command->contentStreamId, $commandHandlingDependencies);
        $this->requireContentStreamToNotBeClosed($command->contentStreamId, $commandHandlingDependencies);
        $streamName = ContentStreamEventStreamName::fromContentStreamId($command->contentStreamId)->getEventStreamName();

        return new EventsToPublish(
            $streamName,
            Events::with(
                new ContentStreamWasClosed(
                    $command->contentStreamId,
                ),
            ),
            $expectedVersion
        );
    }

    private function handleReopenContentStream(
        ReopenContentStream $command,
        CommandHandlingDependencies $commandHandlingDependencies,
    ): EventsToPublish {
        $this->requireContentStreamToExist($command->contentStreamId, $commandHandlingDependencies);
        $expectedVersion = $this->getExpectedVersionOfContentStream($command->contentStreamId, $commandHandlingDependencies);
        $this->requireContentStreamToBeClosed($command->contentStreamId, $commandHandlingDependencies);
        $streamName = ContentStreamEventStreamName::fromContentStreamId($command->contentStreamId)->getEventStreamName();

        return new EventsToPublish(
            $streamName,
            Events::with(
                new ContentStreamWasReopened(
                    $command->contentStreamId,
                    $command->previousState,
                ),
            ),
            $expectedVersion
        );
    }

    /**
     * @throws ContentStreamAlreadyExists
     * @throws ContentStreamDoesNotExistYet
     */
    private function handleForkContentStream(
        ForkContentStream $command,
        CommandHandlingDependencies $commandHandlingDependencies
    ): EventsToPublish {
        $this->requireContentStreamToExist($command->sourceContentStreamId, $commandHandlingDependencies);
        $this->requireContentStreamToNotBeClosed($command->sourceContentStreamId, $commandHandlingDependencies);

        // TODO: This is not great
        $sourceContentStreamVersion = $commandHandlingDependencies->getContentStreamFinder()->findVersionForContentStream($command->sourceContentStreamId);

        $streamName = ContentStreamEventStreamName::fromContentStreamId($command->newContentStreamId)
            ->getEventStreamName();

        return new EventsToPublish(
            $streamName,
            Events::with(
                new ContentStreamWasForked(
                    $command->newContentStreamId,
                    $command->sourceContentStreamId,
                    $sourceContentStreamVersion->unwrap(),
                ),
            ),
            // NO_STREAM to ensure the "fork" happens as the first event of the new content stream
            ExpectedVersion::NO_STREAM()
        );
    }

    private function handleRemoveContentStream(
        RemoveContentStream $command,
        CommandHandlingDependencies $commandHandlingDependencies
    ): EventsToPublish {
        $this->requireContentStreamToExist($command->contentStreamId, $commandHandlingDependencies);
        $expectedVersion = $this->getExpectedVersionOfContentStream($command->contentStreamId, $commandHandlingDependencies);

        $streamName = ContentStreamEventStreamName::fromContentStreamId(
            $command->contentStreamId
        )->getEventStreamName();

        return new EventsToPublish(
            $streamName,
            Events::with(
                new ContentStreamWasRemoved(
                    $command->contentStreamId,
                ),
            ),
            $expectedVersion
        );
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @throws ContentStreamDoesNotExistYet
     */
    protected function requireContentStreamToExist(
        ContentStreamId $contentStreamId,
        CommandHandlingDependencies $commandHandlingDependencies
    ): void {
        $maybeVersion = $commandHandlingDependencies->getContentStreamFinder()->findVersionForContentStream($contentStreamId);
        if ($maybeVersion->isNothing()) {
            throw new ContentStreamDoesNotExistYet(
                'Content stream "' . $contentStreamId->value . '" does not exist yet.',
                1521386692
            );
        }
    }

    protected function requireContentStreamToNotBeClosed(
        ContentStreamId $contentStreamId,
        CommandHandlingDependencies $commandHandlingDependencies
    ): void {
        $contentStreamState = $commandHandlingDependencies->getContentStreamFinder()->findStateForContentStream($contentStreamId);
        if ($contentStreamState === ContentStreamState::STATE_CLOSED) {
            throw new ContentStreamIsClosed(
                'Content stream "' . $contentStreamId->value . '" is closed.',
                1710260081
            );
        }
    }

    protected function requireContentStreamToBeClosed(
        ContentStreamId $contentStreamId,
        CommandHandlingDependencies $commandHandlingDependencies
    ): void {
        $contentStreamState = $commandHandlingDependencies->getContentStreamFinder()->findStateForContentStream($contentStreamId);
        if ($contentStreamState !== ContentStreamState::STATE_CLOSED) {
            throw new ContentStreamIsNotClosed(
                'Content stream "' . $contentStreamId->value . '" is not closed.',
                1710405911
            );
        }
    }

    protected function getExpectedVersionOfContentStream(
        ContentStreamId $contentStreamId,
        CommandHandlingDependencies $commandHandlingDependencies
    ): ExpectedVersion {
        $maybeVersion = $commandHandlingDependencies->getContentStreamFinder()->findVersionForContentStream($contentStreamId);
        return ExpectedVersion::fromVersion(
            $maybeVersion
                ->unwrap()
        );
    }
}
