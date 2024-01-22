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

namespace Neos\ContentRepository\Core\Projection\ContentStream;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\Service\ContentStreamPruner;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\EventStore\Model\Event\Version;
use Neos\EventStore\Model\EventStream\MaybeVersion;

/**
 * Internal - implementation detail of {@see ContentStreamPruner}
 *
 * This projection tracks whether content streams are currently in use or not. Each content stream
 * is first CREATED or FORKED, and then moves to the IN_USE or REBASE_ERROR states; or is removed directly
 * in case of temporary content streams.
 *
 * FORKING: Content streams are forked from a base content stream. It can happen that the base content
 * stream is NO_LONGER_IN_USE, but the child content stream is still IN_USE_BY_WORKSPACE. In this case,
 * the base content stream can go to removed=1 (removed from all projections), but needs to be retained
 * in the event store: If we do a full replay, we need the events of the base content stream before the
 * fork happened to rebuild the child content stream.
 * This logic is done in {@see findUnusedAndRemovedContentStreams}.
 *
 * TEMPORARY content streams: Projections should take care to dispose their temporary content streams,
 * by triggering a ContentStreamWasRemoved event after the content stream is no longer used.
 *
 *           │                       │
 *           │(for root              │during
 *           │ content               │rebase
 *           ▼ stream)               ▼
 *     ┌──────────┐            ┌──────────┐             Temporary
 *     │ CREATED  │            │  FORKED  │────┐          states
 *     └──────────┘            └──────────┘    for
 *           │                       │      temporary
 *           ├───────────────────────┤       content
 *           ▼                       ▼       streams
 * ┌───────────────────┐     ┌──────────────┐  │
 * │IN_USE_BY_WORKSPACE│     │ REBASE_ERROR │  │
 * └───────────────────┘     └──────────────┘  │        Persistent
 *           │                       │         │          States
 *           ▼                       │         │
 * ┌───────────────────┐             │         │
 * │ NO_LONGER_IN_USE  │             │         │
 * └───────────────────┘             │         │
 *           │                       │         │
 *           └──────────┬────────────┘         │
 *                      ▼                      │
 * ┌────────────────────────────────────────┐  │
 * │               removed=1                │  │
 * │ => removed from all other projections  │◀─┘
 * └────────────────────────────────────────┘           Cleanup
 *                      │
 *                      ▼
 * ┌────────────────────────────────────────┐
 * │  completely deleted from event stream  │
 * └────────────────────────────────────────┘
 *
 * @internal
 */
final class ContentStreamFinder implements ProjectionStateInterface
{
    /**
     * the content stream was created, but not yet assigned to a workspace.
     *
     * **temporary state** which should not appear if the system is idle (for content streams which are used with workspaces).
     */
    public const STATE_CREATED = 'CREATED';

    /**
     * STATE_FORKED means the content stream was forked from an existing content stream, but not yet assigned
     * to a workspace.
     *
     * **temporary state** which should not appear if the system is idle (for content streams which are used with workspaces).
     */
    public const STATE_FORKED = 'FORKED';

    /**
     * the content stream is currently referenced as the "active" content stream by a workspace.
     */
    public const STATE_IN_USE_BY_WORKSPACE = 'IN_USE_BY_WORKSPACE';

    /**
     * a workspace was tried to be rebased, and during the rebase an error occured. This is the content stream
     * which contains the errored state - so that we can recover content from it (probably manually)
     */
    public const STATE_REBASE_ERROR = 'REBASE_ERROR';

    /**
     * the content stream is not used anymore, and can be removed.
     */
    public const STATE_NO_LONGER_IN_USE = 'NO_LONGER_IN_USE';

    public function __construct(
        private readonly DbalClientInterface $client,
        private readonly string $tableName,
    ) {
    }

    /**
     * @return iterable<ContentStreamId>
     */
    public function findAllIds(): iterable
    {
        $connection = $this->client->getConnection();
        $contentStreamIds = $connection->executeQuery('SELECT contentstreamid FROM ' . $this->tableName)->fetchFirstColumn();
        return array_map(ContentStreamId::fromString(...), $contentStreamIds);
    }

    /**
     * @param bool $findTemporaryContentStreams if TRUE, will find all content streams not bound to a workspace
     * @return array<int,ContentStreamId>
     */
    public function findUnusedContentStreams(bool $findTemporaryContentStreams): iterable
    {
        $states = [
            self::STATE_NO_LONGER_IN_USE,
            self::STATE_REBASE_ERROR,
        ];

        if ($findTemporaryContentStreams === true) {
            $states[] = self::STATE_CREATED;
            $states[] = self::STATE_FORKED;
        }

        $connection = $this->client->getConnection();
        $contentStreamIds = $connection->executeQuery(
            '
            SELECT contentstreamid FROM ' . $this->tableName . '
                WHERE removed = FALSE
                AND state IN (:state)
            ',
            [
                'state' => $states
            ],
            [
                'state' => Connection::PARAM_STR_ARRAY
            ]
        )->fetchFirstColumn();
        return array_map(ContentStreamId::fromString(...), $contentStreamIds);
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @return string one of the self::STATE_* constants
     */
    public function findStateForContentStream(ContentStreamId $contentStreamId): ?string
    {
        $connection = $this->client->getConnection();
        /* @var $state string|false */
        $state = $connection->executeQuery(
            '
            SELECT state FROM ' . $this->tableName . '
                WHERE contentstreamid = :contentStreamId
                AND removed = FALSE
            ',
            [
                'contentStreamId' => $contentStreamId->value,
            ]
        )->fetchOne();

        if ($state === false) {
            return null;
        }

        return $state;
    }

    /**
     * @return array<int,ContentStreamId>
     */
    public function findUnusedAndRemovedContentStreams(): iterable
    {
        $connection = $this->client->getConnection();
        $contentStreamIds = $connection->executeQuery(
            '
            WITH RECURSIVE transitiveUsedContentStreams (contentstreamid) AS (
                    -- initial case: find all content streams currently in direct use by a workspace
                    SELECT contentstreamid FROM ' . $this->tableName . '
                    WHERE
                        state = :inUseState
                        AND removed = false
                UNION
                    -- now, when a content stream is in use by a workspace, its source content stream is
                    -- also "transitively" in use.
                    SELECT sourceContentStreamId FROM ' . $this->tableName . '
                    JOIN transitiveUsedContentStreams
                        ON ' . $this->tableName . '.contentStreamId
                            = transitiveUsedContentStreams.contentStreamId
                    WHERE
                        ' . $this->tableName . '.sourceContentStreamId IS NOT NULL
            )

            -- now, we check for removed content streams which we do not need anymore transitively
            SELECT contentstreamid FROM ' . $this->tableName . ' AS cs
                WHERE removed = true
                AND NOT EXISTS (
                    SELECT 1
                    FROM transitiveUsedContentStreams
                    WHERE
                        cs.contentstreamid = transitiveUsedContentStreams.contentstreamid
                )
            ',
            [
                'inUseState' => self::STATE_IN_USE_BY_WORKSPACE
            ]
        )->fetchFirstColumn();
        return array_map(ContentStreamId::fromString(...), $contentStreamIds);
    }

    public function findVersionForContentStream(ContentStreamId $contentStreamId): MaybeVersion
    {
        $connection = $this->client->getConnection();
        /* @var $state string|false */
        $version = $connection->executeQuery(
            '
            SELECT version FROM ' . $this->tableName . '
                WHERE contentStreamId = :contentStreamId
            ',
            [
                'contentStreamId' => $contentStreamId->value,
            ]
        )->fetchOne();

        if ($version === false) {
            return MaybeVersion::fromVersionOrNull(null);
        }

        return MaybeVersion::fromVersionOrNull(Version::fromInteger($version));
    }

    public function hasContentStream(ContentStreamId $contentStreamId): bool
    {
        $connection = $this->client->getConnection();
        /* @var $state string|false */
        $version = $connection->executeQuery(
            '
            SELECT version FROM ' . $this->tableName . '
                WHERE contentStreamId = :contentStreamId
            ',
            [
                'contentStreamId' => $contentStreamId->value
            ]
        )->fetchOne();

        return $version !== false;
    }
}
