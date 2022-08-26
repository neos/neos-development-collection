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

namespace Neos\ContentRepository\Feature;

use Neos\ContentRepository\CommandHandler\CommandHandlerInterface;
use Neos\ContentRepository\CommandHandler\CommandInterface;
use Neos\ContentRepository\ContentRepository;
use Neos\ContentRepository\EventStore\Events;
use Neos\ContentRepository\EventStore\EventsToPublish;
use Neos\ContentRepository\Feature\ContentStreamCreation\Command\CreateContentStream;
use Neos\ContentRepository\Feature\ContentStreamCreation\Event\ContentStreamWasCreated;
use Neos\ContentRepository\Feature\ContentStreamForking\Command\ForkContentStream;
use Neos\ContentRepository\Feature\ContentStreamForking\Event\ContentStreamWasForked;
use Neos\ContentRepository\Feature\ContentStreamRemoval\Command\RemoveContentStream;
use Neos\ContentRepository\Feature\ContentStreamRemoval\Event\ContentStreamWasRemoved;
use Neos\EventStore\Model\EventStream\ExpectedVersion;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\Feature\Common\Exception\ContentStreamAlreadyExists;
use Neos\ContentRepository\Feature\Common\Exception\ContentStreamDoesNotExistYet;

/**
 * INTERNALS. Only to be used from WorkspaceCommandHandler!!!
 *
 * ContentStreamCommandHandler
 */
final class ContentStreamCommandHandler implements CommandHandlerInterface
{
    public function canHandle(CommandInterface $command): bool
    {
        return $command instanceof CreateContentStream
            || $command instanceof ForkContentStream
            || $command instanceof RemoveContentStream;
    }

    public function handle(CommandInterface $command, ContentRepository $contentRepository): EventsToPublish
    {
        if ($command instanceof CreateContentStream) {
            return $this->handleCreateContentStream($command, $contentRepository);
        } elseif ($command instanceof ForkContentStream) {
            return $this->handleForkContentStream($command, $contentRepository);
        } elseif ($command instanceof RemoveContentStream) {
            return $this->handleRemoveContentStream($command, $contentRepository);
        }

        throw new \RuntimeException('Unsupported command type');
    }

    /**
     * @throws ContentStreamAlreadyExists
     */
    private function handleCreateContentStream(
        CreateContentStream $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $this->requireContentStreamToNotExistYet($command->contentStreamIdentifier, $contentRepository);
        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($command->contentStreamIdentifier)
            ->getEventStreamName();

        return new EventsToPublish(
            $streamName,
            Events::with(
                new ContentStreamWasCreated(
                    $command->contentStreamIdentifier,
                    $command->initiatingUserIdentifier
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
        $this->requireContentStreamToExist($command->sourceContentStreamIdentifier, $contentRepository);
        $this->requireContentStreamToNotExistYet($command->newContentStreamIdentifier, $contentRepository);

        $sourceContentStreamVersion = $contentRepository->getContentStreamFinder()
            ->findVersionForContentStream($command->sourceContentStreamIdentifier);

        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier($command->newContentStreamIdentifier)
            ->getEventStreamName();

        return new EventsToPublish(
            $streamName,
            Events::with(
                new ContentStreamWasForked(
                    $command->newContentStreamIdentifier,
                    $command->sourceContentStreamIdentifier,
                    $sourceContentStreamVersion->unwrap(),
                    $command->initiatingUserIdentifier
                ),
            ),
            ExpectedVersion::ANY()
        );
    }

    private function handleRemoveContentStream(
        RemoveContentStream $command,
        ContentRepository $contentRepository
    ): EventsToPublish {
        $this->requireContentStreamToExist($command->contentStreamIdentifier, $contentRepository);

        $streamName = ContentStreamEventStreamName::fromContentStreamIdentifier(
            $command->contentStreamIdentifier
        )->getEventStreamName();

        return new EventsToPublish(
            $streamName,
            Events::with(
                new ContentStreamWasRemoved(
                    $command->contentStreamIdentifier,
                    $command->initiatingUserIdentifier
                ),
            ),
            ExpectedVersion::ANY()
        );
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @throws ContentStreamAlreadyExists
     */
    protected function requireContentStreamToNotExistYet(
        ContentStreamIdentifier $contentStreamIdentifier,
        ContentRepository $contentRepository
    ): void {
        if ($contentRepository->getContentStreamFinder()->hasContentStream($contentStreamIdentifier)) {
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
    protected function requireContentStreamToExist(
        ContentStreamIdentifier $contentStreamIdentifier,
        ContentRepository $contentRepository
    ): void {
        if (!$contentRepository->getContentStreamFinder()->hasContentStream($contentStreamIdentifier)) {
            throw new ContentStreamDoesNotExistYet(
                'Content stream "' . $contentStreamIdentifier . '" does not exist yet.',
                1521386692
            );
        }
    }
}
