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
use Neos\ContentRepository\Core\EventStore\EventsToPublish;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Command\CloseContentStream;
use Neos\ContentRepository\Core\Feature\ContentStreamClosing\Command\ReopenContentStream;
use Neos\ContentRepository\Core\Feature\ContentStreamForking\Command\ForkContentStream;
use Neos\ContentRepository\Core\Feature\ContentStreamRemoval\Command\RemoveContentStream;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamAlreadyExists;
use Neos\ContentRepository\Core\SharedModel\Exception\ContentStreamDoesNotExistYet;

/**
 * INTERNALS. Only to be used from WorkspaceCommandHandler!!!
 *
 * @internal from userland, you'll use ContentRepository::handle to dispatch commands
 * FIXME try to fully get rid of this handler :D and the external commands!
 */
final class ContentStreamCommandHandler implements CommandHandlerInterface
{
    use ContentStreamHandling;

    public function canHandle(CommandInterface $command): bool
    {
        return method_exists($this, 'handle' . (new \ReflectionClass($command))->getShortName());
    }

    public function handle(CommandInterface $command, CommandHandlingDependencies $commandHandlingDependencies): EventsToPublish
    {
        return match ($command::class) {
            CloseContentStream::class => $this->handleCloseContentStream($command, $commandHandlingDependencies),
            ReopenContentStream::class => $this->handleReopenContentStream($command, $commandHandlingDependencies),
            ForkContentStream::class => $this->handleForkContentStream($command, $commandHandlingDependencies),
            RemoveContentStream::class => $this->handleRemoveContentStream($command, $commandHandlingDependencies),
            default => throw new \DomainException('Cannot handle commands of class ' . get_class($command), 1710408206),
        };
    }

    private function handleCloseContentStream(
        CloseContentStream $command,
        CommandHandlingDependencies $commandHandlingDependencies,
    ): EventsToPublish {
        return $this->closeContentStream($command->contentStreamId, $commandHandlingDependencies);
    }

    private function handleReopenContentStream(
        ReopenContentStream $command,
        CommandHandlingDependencies $commandHandlingDependencies,
    ): EventsToPublish {
        return $this->reopenContentStream($command->contentStreamId, $command->previousState, $commandHandlingDependencies);
    }

    /**
     * @throws ContentStreamAlreadyExists
     * @throws ContentStreamDoesNotExistYet
     */
    private function handleForkContentStream(
        ForkContentStream $command,
        CommandHandlingDependencies $commandHandlingDependencies
    ): EventsToPublish {
        return $this->forkContentStream($command->newContentStreamId, $command->sourceContentStreamId, $commandHandlingDependencies);
    }

    private function handleRemoveContentStream(
        RemoveContentStream $command,
        CommandHandlingDependencies $commandHandlingDependencies
    ): EventsToPublish {
        return $this->removeContentStream($command->contentStreamId, $commandHandlingDependencies);
    }
}
