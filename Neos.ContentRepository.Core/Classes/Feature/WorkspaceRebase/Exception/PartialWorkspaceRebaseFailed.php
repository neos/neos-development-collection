<?php

/*
 * This file is part of the Neos.ContentRepository.Core package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\WorkspaceRebase\Exception;

use Neos\ContentRepository\Core\Feature\WorkspaceRebase\ConflictingEvents;

/**
 * Thrown if the partial publish/discard cannot work because the events cannot be reordered as filtered.
 *
 * This can happen for cases like attempting to publish a removal first and wanting as remaining change
 * a node move out of the removed descendants or publishing a node variant creation before the node is created.
 *
 * We cannot reliably detect these cases in advance but in case the workspace is up-to-date its most likely such
 * an ordering conflict.
 *
 * To solve the problem the partial operation should be retried with a different filter _or_ a full publish/discard is required.
 *
 * If the workspace is outdated we cannot know for sure but suspect first that the conflict arose due to changes
 * in the base workspace, thus we throw {@see WorkspaceRebaseFailed} instead.
 * A forced rebase then might not solve the problem if It's because the order of events cannot be changed.
 * But attempting a second partial publish/discard (with up-to-date workspace) this exception will be thrown and can be reacted upon.
 *
 * @see WorkspaceRebaseFailed which is thrown instead if the workspace is also outdated
 * @api this exception contains information which events cannot be reordered
 */
final class PartialWorkspaceRebaseFailed extends \RuntimeException
{
    private function __construct(
        public readonly ConflictingEvents $conflictingEvents,
        string $message,
        int $code,
        ?\Throwable $previous,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function duringPartialPublish(ConflictingEvents $conflictingEvents): self
    {
        return new self(
            $conflictingEvents,
            sprintf('Publication failed, events cannot be reordered as filtered: %s', self::renderMessage($conflictingEvents)),
            1729974980,
            $conflictingEvents->first()?->getException()
        );
    }

    public static function duringPartialDiscard(ConflictingEvents $conflictingEvents): self
    {
        return new self(
            $conflictingEvents,
            sprintf('Discard failed, events cannot be reordered as filtered: %s', self::renderMessage($conflictingEvents)),
            1729974982,
            $conflictingEvents->first()?->getException()
        );
    }

    private static function renderMessage(ConflictingEvents $conflictingEvents): string
    {
        $firstConflict = $conflictingEvents->first();
        return sprintf('"%s" and %d further ordering conflicts', $firstConflict?->getException()->getMessage(), count($conflictingEvents) - 1);
    }
}
