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
 * @internal
 */
final class ContentStreamFinder implements ProjectionStateInterface
{
    public const STATE_CREATED = 'CREATED';
    public const STATE_IN_USE_BY_WORKSPACE = 'IN_USE_BY_WORKSPACE';
    public const STATE_REBASING = 'REBASING';
    public const STATE_REBASE_ERROR = 'REBASE_ERROR';
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
        $databaseRows = $connection->executeQuery(
            '
            SELECT contentStreamId FROM ' . $this->tableName . '
            ',
        )->fetchAllAssociative();

        return array_map(
            fn (array $databaseRow): ContentStreamId => ContentStreamId::fromString(
                $databaseRow['contentStreamId']
            ),
            $databaseRows
        );
    }

    /**
     * @return array<int,ContentStreamId>
     */
    public function findUnusedContentStreams(): iterable
    {
        $connection = $this->client->getConnection();
        $databaseRows = $connection->executeQuery(
            '
            SELECT contentStreamId FROM ' . $this->tableName . '
                WHERE removed = FALSE
                AND state IN (:state)
            ',
            [
                'state' => [
                    self::STATE_NO_LONGER_IN_USE,
                    self::STATE_REBASE_ERROR,
                ]
            ],
            [
                'state' => Connection::PARAM_STR_ARRAY
            ]
        )->fetchAllAssociative();

        $contentStreams = [];
        foreach ($databaseRows as $databaseRow) {
            $contentStreams[] = ContentStreamId::fromString($databaseRow['contentStreamId']);
        }

        return $contentStreams;
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
                WHERE contentStreamId = :contentStreamId
                AND removed = FALSE
            ',
            [
                'contentStreamId' => $contentStreamId->jsonSerialize()
            ]
        )->fetchColumn();

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
        $databaseRows = $connection->executeQuery(
            '
            WITH RECURSIVE transitiveUsedContentStreams (contentStreamId) AS (
                    -- initial case: find all content streams currently in direct use by a workspace
                    SELECT contentStreamId FROM ' . $this->tableName . '
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
            SELECT contentStreamId FROM ' . $this->tableName . ' AS cs
                WHERE removed = true
                AND NOT EXISTS (
                    SELECT 1
                    FROM transitiveUsedContentStreams
                    WHERE
                        cs.contentStreamId = transitiveUsedContentStreams.contentStreamId
                )
            ',
            [
                'inUseState' => self::STATE_IN_USE_BY_WORKSPACE
            ]
        )->fetchAll();

        $contentStreams = [];
        foreach ($databaseRows as $databaseRow) {
            $contentStreams[] = ContentStreamId::fromString($databaseRow['contentStreamId']);
        }

        return $contentStreams;
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
                'contentStreamId' => $contentStreamId->jsonSerialize()
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
                'contentStreamId' => $contentStreamId->jsonSerialize()
            ]
        )->fetchOne();

        return $version !== false;
    }
}
