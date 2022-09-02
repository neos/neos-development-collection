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
use Neos\ContentRepository\Core\ContentRepository;
use Neos\ContentRepository\Core\EventStore\Events;
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Command\CreateContentStream;
use Neos\ContentRepository\Core\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Command\ForkContentStream;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Command\RemoveContentStream;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\EventStore\Model\EventStream\ExpectedVersion;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamAlreadyExists;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;

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

    public function handle(CommandInterface $command, ContentRepository $contentRepository): EventsToPublish
    {
        return match ($command::class) {
            CreateContentStream::class => $this->handleCreateContentStream($command, $contentRepository),
            ForkContentStream::class => $this->handleForkContentStream($command, $contentRepository),
            RemoveContentStream::class => $this->handleRemoveContentStream($command, $contentRepository),
        };
    }

    /**
     * @throws ContentStreamAlreadyExists
     */
    private function handleCreateContentStream(
        CreateContentStream $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $this->requireContentStreamToNotExistYet($command->contentStreamId, $contentRepository);
        $streamName = ContentStreamEventStreamName::fromContentStreamId($command->contentStreamId)
            ->getEventStreamName();

        return new EventsToPublish(
            $streamName,
            Events::with(
                new ContentStreamWasCreated(
                    $command->contentStreamId,
                    $command->initiatingUserId
                )
            ),
            ExpectedVersion::NO_STREAM()
        );
    }

    /**
     * @throws ContentStreamAlreadyExists
     * @throws ContentStreamDoesNotExistYet
     */
    private function handleForkContentStream(
        ForkContentStream $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $this->requireContentStreamToExist($command->sourceContentStreamId, $contentRepository);
        $this->requireContentStreamToNotExistYet($command->newContentStreamId, $contentRepository);

        $sourceContentStreamVersion = $contentRepository->getContentStreamFinder()
            ->findVersionForContentStream($command->sourceContentStreamId);

        $streamName = ContentStreamEventStreamName::fromContentStreamId($command->newContentStreamId)
            ->getEventStreamName();

        return new EventsToPublish(
            $streamName,
            Events::with(
                new ContentStreamWasForked(
                    $command->newContentStreamId,
                    $command->sourceContentStreamId,
                    $sourceContentStreamVersion->unwrap(),
                    $command->initiatingUserId
                ),
            ),
            ExpectedVersion::ANY()
        );
    }

    private function handleRemoveContentStream(
        RemoveContentStream $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $this->requireContentStreamToExist($command->contentStreamId, $contentRepository);

        $streamName = ContentStreamEventStreamName::fromContentStreamId(
            $command->contentStreamId
        )->getEventStreamName();

        return new EventsToPublish(
            $streamName,
            Events::with(
                new ContentStreamWasRemoved(
                    $command->contentStreamId,
                    $command->initiatingUserId
                ),
            ),
            ExpectedVersion::ANY()
        );
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @throws ContentStreamAlreadyExists
     */
    protected function requireContentStreamToNotExistYet(
        ContentStreamId $contentStreamId,
        ContentRepository $contentRepository
    ): void {
        if ($contentRepository->getContentStreamFinder()->hasContentStream($contentStreamId)) {
            throw new ContentStreamAlreadyExists(
                'Content stream "' . $contentStreamId . '" already exists.',
                1521386345
            );
        }
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @throws ContentStreamDoesNotExistYet
     */
    protected function requireContentStreamToExist(
        ContentStreamId $contentStreamId,
        ContentRepository $contentRepository
    ): void {
        if (!$contentRepository->getContentStreamFinder()->hasContentStream($contentStreamId)) {
            throw new ContentStreamDoesNotExistYet(
                'Content stream "' . $contentStreamId . '" does not exist yet.',
                1521386692
            );
        }
    }

}
