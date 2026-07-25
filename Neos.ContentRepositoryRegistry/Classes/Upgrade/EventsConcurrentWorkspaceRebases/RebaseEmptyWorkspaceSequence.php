<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\EventsConcurrentWorkspaceRebases;

enum RebaseEmptyWorkspaceSequence: string
{
    // Order defines sequence
    case ContentStreamWasClosed = 'ContentStreamWasClosed';
    case ContentStreamWasForked = 'ContentStreamWasForked';
    case WorkspaceWasRebased = 'WorkspaceWasRebased';
    case ContentStreamWasRemoved = 'ContentStreamWasRemoved';

    case ENDED = '[ENDED]';

    public static function start(): self
    {
        return self::cases()[0];
    }

    public function next(): self
    {
        foreach (self::cases() as $index => $case) {
            if ($case === $this) {
                return self::cases()[$index + 1] ?? throw new \RuntimeException('Reached end');
            }
        }
        throw new \RuntimeException(sprintf('Fatal cannot happen'), 1781725195);
    }
}
