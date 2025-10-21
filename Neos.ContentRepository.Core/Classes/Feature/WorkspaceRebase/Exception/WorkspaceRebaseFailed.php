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

use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\DiscardWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishIndividualNodesFromWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspacePublication\Command\PublishWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Command\RebaseWorkspace;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\ConflictingEvents;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Dto\RebaseErrorHandlingStrategy;

/**
 * Thrown if the workspace was outdated and an automatic rebase failed due to conflicts.
 *
 * No changes to the workspace/content-stream were made and the operation was aborted.
 *
 * Affected workspace operations:
 *
 * *Workspace publish*
 * Via the commands {@see PublishWorkspace} or {@see PublishIndividualNodesFromWorkspace}.
 *
 * *Workspace discard*
 * Via the commands {@see DiscardWorkspace} or {@see DiscardIndividualNodesFromWorkspace}.
 *
 * *Workspace rebase*
 * Via the command {@see RebaseWorkspace}, if the strategy was set to {@see RebaseErrorHandlingStrategy::STRATEGY_FAIL}.
 *
 * Related: {@see PartialWorkspaceRebaseFailed}
 *
 * @api this exception contains information about what exactly went wrong during rebase
 */
final class WorkspaceRebaseFailed extends \RuntimeException
{
    private function __construct(
        public readonly ConflictingEvents $conflictingEvents,
        string $message,
        int $code,
        ?\Throwable $previous,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public static function duringRebase(ConflictingEvents $conflictingEvents): self
    {
        return new self(
            $conflictingEvents,
            sprintf('Rebase failed: %s', self::renderMessage($conflictingEvents)),
            1729974936,
            $conflictingEvents->first()?->getException()
        );
    }

    public static function duringPublish(ConflictingEvents $conflictingEvents): self
    {
        return new self(
            $conflictingEvents,
            sprintf('Publication failed: %s', self::renderMessage($conflictingEvents)),
            1729974980,
            $conflictingEvents->first()?->getException()
        );
    }

    public static function duringDiscard(ConflictingEvents $conflictingEvents): self
    {
        return new self(
            $conflictingEvents,
            sprintf('Discard failed: %s', self::renderMessage($conflictingEvents)),
            1729974982,
            $conflictingEvents->first()?->getException()
        );
    }

    private static function renderMessage(ConflictingEvents $conflictingEvents): string
    {
        $firstConflict = $conflictingEvents->first();
        return sprintf('"%s"%s', $firstConflict?->getException()->getMessage(), count($conflictingEvents) > 1 ? sprintf(' and %d further conflicts', count($conflictingEvents) - 1) : '');
    }
}
