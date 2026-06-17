<?php

declare(strict_types=1);

namespace Neos\ContentRepositoryRegistry\Upgrade\EventsDuplicateContentStreamRemoval;

enum ChangeBaseWorkspaceSequence: string
{
    // Order defines sequence
    case ContentStreamWasForked = 'ContentStreamWasForked';
    case WorkspaceBaseWorkspaceWasChanged = 'WorkspaceBaseWorkspaceWasChanged';
    case ContentStreamWasRemoved = 'ContentStreamWasRemoved';

    case ENDED = '[Ended]';

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
