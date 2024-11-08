<?php

declare(strict_types=1);

namespace Neos\ContentRepository\Core\CommandHandler;

use Neos\ContentRepository\Core\Feature\Common\RebasableToOtherWorkspaceInterface;

/**
 * Optional interface for all content repository command serializer
 *
 * See {@see RebasableToOtherWorkspaceInterface} for more details regarding serialized commands.
 *
 * @internal no public API, because commands are no extension points of the CR
 */
interface CommandSerializerInterface
{
    public function canSerialize(PublicCommandInterface $command): bool;

    public function serialize(PublicCommandInterface $command, CommandHandlingDependencies $commandHandlingDependencies): RebasableToOtherWorkspaceInterface;
}
