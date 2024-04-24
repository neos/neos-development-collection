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
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamState;
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
 * @see ContentStreamState
 *
 * @internal
 */
final readonly class ContentStreamFinder implements ProjectionStateInterface
{
    public function __construct(
        private DbalClientInterface $client,
        private string $tableName,
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
            ContentStreamState::STATE_NO_LONGER_IN_USE,
            ContentStreamState::STATE_REBASE_ERROR,
        ];

        if ($findTemporaryContentStreams === true) {
            $states[] = ContentStreamState::STATE_CREATED;
            $states[] = ContentStreamState::STATE_FORKED;
        }

        $connection = $this->client->getConnection();
        $contentStreamIds = $connection->executeQuery(
            '
            SELECT contentstreamid FROM ' . $this->tableName . '
                WHERE removed = FALSE
                AND state IN (:states)
            ',
            [
                'states' => array_map(
                    fn (ContentStreamState $contentStreamState): string => $contentStreamState->value,
                    $states
                )
            ],
            [
                'states' => Connection::PARAM_STR_ARRAY
            ]
        )->fetchFirstColumn();

        return array_map(ContentStreamId::fromString(...), $contentStreamIds);
    }

    public function findStateForContentStream(ContentStreamId $contentStreamId): ?ContentStreamState
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

        return ContentStreamState::tryFrom($state ?: '');
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
                'inUseState' => ContentStreamState::STATE_IN_USE_BY_WORKSPACE->value
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
