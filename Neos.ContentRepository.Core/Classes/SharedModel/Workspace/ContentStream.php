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

namespace Neos\ContentRepository\Core\SharedModel\Workspace;

use Neos\ContentRepository\Core\Service\ContentStreamPruner;
use Neos\EventStore\Model\Event\Version;

/**
 * Content Stream Read Model
 *
 * This model reflects if content streams are currently in use or not. Each content stream
 * is first CREATED or FORKED, and then moves to the IN_USE or REBASE_ERROR states; or is removed directly
 * in case of temporary content streams.
 *
 * FORKING: Content streams are forked from a base content stream. It can happen that the base content
 * stream is NO_LONGER_IN_USE, but the child content stream is still IN_USE_BY_WORKSPACE. In this case,
 * the base content stream can go to removed=true (removed from the content graph), but needs to be retained
 * in the event store: If we do a full replay, we need the events of the base content stream before the
 * fork happened to rebuild the child content stream.
 * This logic is done in {@see ContentStreamPruner::findUnusedAndRemovedContentStreamIds()}.
 *
 * TEMPORARY content streams: Projections should take care to dispose their temporary content streams,
 * by triggering a ContentStreamWasRemoved event after the content stream is no longer used.
 *
 * The different status a content stream can be in
 *
 *            │                       │
 *            │(for root              │during
 *            │ content               │rebase
 *            ▼ stream)               ▼
 *      ┌──────────┐            ┌──────────┐             Temporary
 *      │ CREATED  │            │  FORKED  │────┐          status
 *      └──────────┘            └──────────┘    for
 *            │                       │      temporary
 *            ├───────────────────────┤       content
 *            ▼                       │       streams
 *  ┌───────────────────┐             │         │
 *  │IN_USE_BY_WORKSPACE│             │         │
 *  └───────────────────┘             │         │        Persistent
 *            │                       │         │          status
 *            ▼                       │         │
 *  ┌───────────────────┐             │         │
 *  │ NO_LONGER_IN_USE  │             │         │
 *  └───────────────────┘             │         │
 *            │                       │         │
 *            └──────────┬────────────┘         │
 *                       ▼                      │
 *  ┌────────────────────────────────────────┐  │
 *  │              removed=true              │  │
 *  │     => removed from content graph      │◀─┘
 *  └────────────────────────────────────────┘           Cleanup
 *                       │
 *                       ▼
 *  ┌────────────────────────────────────────┐
 *  │  completely deleted from event stream  │
 *  └────────────────────────────────────────┘
 *
 * @api
 */
final readonly class ContentStream
{
    /**
     * @internal
     */
    public function __construct(
        public ContentStreamId $id,
        public ?ContentStreamId $sourceContentStreamId,
        public ContentStreamStatus $status,
        public Version $version,
        public bool $removed
    ) {
    }
}
