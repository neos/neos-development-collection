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

namespace Neos\ContentRepository\Core\SharedModel\Workspace;

/**
 * The different states a content stream can be in
 *
 *
 *            │                       │
 *            │(for root              │during
 *            │ content               │rebase
 *            ▼ stream)               ▼
 *      ┌──────────┐            ┌──────────┐             Temporary
 *      │ CREATED  │            │  FORKED  │────┐          states
 *      └──────────┘            └──────────┘    for
 *            │                       │      temporary
 *            ├───────────────────────┤       content
 *            ▼                       ▼       streams
 *  ┌───────────────────┐     ┌──────────────┐  │
 *  │IN_USE_BY_WORKSPACE│     │ REBASE_ERROR │  │
 *  └───────────────────┘     └──────────────┘  │        Persistent
 *            │                       │         │          States
 *            ▼                       │         │
 *  ┌───────────────────┐             │         │
 *  │ NO_LONGER_IN_USE  │             │         │
 *  └───────────────────┘             │         │
 *            │                       │         │
 *            └──────────┬────────────┘         │
 *                       ▼                      │
 *  ┌────────────────────────────────────────┐  │
 *  │               removed=1                │  │
 *  │ => removed from all other projections  │◀─┘
 *  └────────────────────────────────────────┘           Cleanup
 *                       │
 *                       ▼
 *  ┌────────────────────────────────────────┐
 *  │  completely deleted from event stream  │
 *  └────────────────────────────────────────┘
 *
 * @api
 */
enum ContentStreamStatus: string implements \JsonSerializable
{
    /**
     * the content stream was created, but not yet assigned to a workspace.
     *
     * **temporary state** which should not appear if the system is idle (for content streams which are used with workspaces).
     */
    case CREATED = 'CREATED';

    /**
     * FORKED means the content stream was forked from an existing content stream, but not yet assigned
     * to a workspace.
     *
     * **temporary state** which should not appear if the system is idle (for content streams which are used with workspaces).
     */
    case FORKED = 'FORKED';

    /**
     * the content stream is currently referenced as the "active" content stream by a workspace.
     */
    case IN_USE_BY_WORKSPACE = 'IN_USE_BY_WORKSPACE';

    /**
     * a workspace was tried to be rebased, and during the rebase an error occured. This is the content stream
     * which contains the errored state - so that we can recover content from it (probably manually)
     */
    case REBASE_ERROR = 'REBASE_ERROR';

    /**
     * the content stream was closed and must no longer accept new events
     */
    case CLOSED = 'CLOSED';

    /**
     * the content stream is not used anymore, and can be removed.
     */
    case NO_LONGER_IN_USE = 'NO_LONGER_IN_USE';

    public static function fromString(string $value): self
    {
        return self::from($value);
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
