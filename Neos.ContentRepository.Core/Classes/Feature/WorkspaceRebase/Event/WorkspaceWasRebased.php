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

namespace Neos\ContentRepository\Core\Feature\WorkspaceRebase\Event;

use Neos\ContentRepository\Core\EventStore\EventInterface;
use Neos\ContentRepository\Core\Feature\Common\EmbedsWorkspaceName;
use Neos\ContentRepository\Core\Feature\WorkspaceRebase\Dto\RebaseErrorHandlingStrategy;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\EventStore\Model\Event\SequenceNumber;

/**
 * @api events are the persistence-API of the content repository
 */
final readonly class WorkspaceWasRebased implements EventInterface, EmbedsWorkspaceName
{
    public function __construct(
        public WorkspaceName $workspaceName,
        /**
         * The new content stream ID (after the rebase was successful)
         */
        public ContentStreamId $newContentStreamId,
        /**
         * The old content stream ID (which is not active anymore now)
         */
        public ContentStreamId $previousContentStreamId,
        /**
         * @var list<SequenceNumber>
         * @internal actually conflicting event's sequence numbers: only for debugging & testing please use {@see hasSkippedEvents()} which is API instead.
         */
        public array $skippedEvents
    ) {
    }

    public function getWorkspaceName(): WorkspaceName
    {
        return $this->workspaceName;
    }

    /**
     * Indicates if failing changes were discarded during a forced rebase {@see RebaseErrorHandlingStrategy::STRATEGY_FORCE} or if all events in the workspace were kept.
     */
    public function hasSkippedEvents(): bool
    {
        return $this->skippedEvents !== [];
    }

    public static function fromArray(array $values): self
    {
        return new self(
            WorkspaceName::fromString($values['workspaceName']),
            ContentStreamId::fromString($values['newContentStreamId']),
            ContentStreamId::fromString($values['previousContentStreamId']),
            array_map(SequenceNumber::fromInteger(...), $values['skippedEvents'] ?? [])
        );
    }

    public function jsonSerialize(): array
    {
        return [
            'workspaceName' => $this->workspaceName,
            'newContentStreamId' => $this->newContentStreamId,
            'previousContentStreamId' => $this->previousContentStreamId,
            // todo SequenceNumber is NOT jsonSerializeAble!!!
            'skippedEvents' => array_map(fn (SequenceNumber $sequenceNumber) => $sequenceNumber->value, $this->skippedEvents)
        ];
    }
}
