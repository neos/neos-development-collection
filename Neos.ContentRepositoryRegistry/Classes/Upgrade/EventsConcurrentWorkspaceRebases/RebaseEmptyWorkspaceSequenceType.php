<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\EventsConcurrentWorkspaceRebases;

enum RebaseEmptyWorkspaceSequenceType: string
{
    // Order defines sequence
    case ContentStreamWasClosed = 'ContentStreamWasClosed';
    case ContentStreamWasForked = 'ContentStreamWasForked';
    case WorkspaceWasRebased = 'WorkspaceWasRebased';
    case ContentStreamWasRemoved = 'ContentStreamWasRemoved';

    public static function start(): self
    {
        return self::cases()[0];
    }

    public function next(): ?self
    {
        foreach (self::cases() as $index => $case) {
            if ($case === $this) {
                return self::cases()[$index + 1] ?? null;
            }
        }
        return null;
    }
}
