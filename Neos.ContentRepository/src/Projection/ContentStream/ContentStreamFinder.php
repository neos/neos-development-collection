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

namespace Neos\ContentRepository\Projection\ContentStream;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\Flow\Annotations as Flow;

/**
 * @Flow\Scope("singleton")
 */
final class ContentStreamFinder
{
    public const STATE_CREATED = 'CREATED';
    public const STATE_IN_USE_BY_WORKSPACE = 'IN_USE_BY_WORKSPACE';
    public const STATE_REBASING = 'REBASING';
    public const STATE_REBASE_ERROR = 'REBASE_ERROR';
    public const STATE_NO_LONGER_IN_USE = 'NO_LONGER_IN_USE';

    public function __construct(private readonly DbalClientInterface $client)
    {
    }

    /**
     * @return iterable<ContentStreamIdentifier>
     */
    public function findAllIdentifiers(): iterable
    {
        $connection = $this->client->getConnection();
        $databaseRows = $connection->executeQuery(
            '
            SELECT contentStreamIdentifier FROM neos_contentrepository_projection_contentstream_v1
            ',
        )->fetchAllAssociative();

        return array_map(
            fn (array $databaseRow): ContentStreamIdentifier => ContentStreamIdentifier::fromString(
                $databaseRow['contentStreamIdentifier']
            ),
            $databaseRows
        );
    }

    /**
     * @return array<int,ContentStreamIdentifier>
     */
    public function findUnusedContentStreams(): iterable
    {
        $connection = $this->client->getConnection();
        $databaseRows = $connection->executeQuery(
            '
            SELECT contentStreamIdentifier FROM neos_contentrepository_projection_contentstream_v1
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
            $contentStreams[] = ContentStreamIdentifier::fromString($databaseRow['contentStreamIdentifier']);
        }

        return $contentStreams;
    }

    /**
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @return string one of the self::STATE_* constants
     */
    public function findStateForContentStream(ContentStreamIdentifier $contentStreamIdentifier): ?string
    {
        $connection = $this->client->getConnection();
        /* @var $state string|false */
        $state = $connection->executeQuery(
            '
            SELECT state FROM neos_contentrepository_projection_contentstream_v1
                WHERE contentStreamIdentifier = :contentStreamIdentifier
                AND removed = FALSE
            ',
            [
                'contentStreamIdentifier' => $contentStreamIdentifier->jsonSerialize()
            ]
        )->fetchColumn();

        if ($state === false) {
            return null;
        }

        return $state;
    }

    /**
     * @return array<int,ContentStreamIdentifier>
     */
    public function findUnusedAndRemovedContentStreams(): iterable
    {
        $connection = $this->client->getConnection();
        $databaseRows = $connection->executeQuery(
            '
            WITH RECURSIVE transitiveUsedContentStreams (contentStreamIdentifier) AS (
                    -- initial case: find all content streams currently in direct use by a workspace
                    SELECT contentStreamIdentifier FROM neos_contentrepository_projection_contentstream_v1
                    WHERE
                        state = :inUseState
                        AND removed = false
                UNION
                    -- now, when a content stream is in use by a workspace, its source content stream is
                    -- also "transitively" in use.
                    SELECT sourceContentStreamIdentifier FROM neos_contentrepository_projection_contentstream_v1
                    JOIN transitiveUsedContentStreams
                        ON neos_contentrepository_projection_contentstream_v1.contentStreamIdentifier
                            = transitiveUsedContentStreams.contentStreamIdentifier
                    WHERE
                        neos_contentrepository_projection_contentstream_v1.sourceContentStreamIdentifier IS NOT NULL
            )

            -- now, we check for removed content streams which we do not need anymore transitively
            SELECT contentStreamIdentifier FROM neos_contentrepository_projection_contentstream_v1 AS cs
                WHERE removed = true
                AND NOT EXISTS (
                    SELECT 1
                    FROM transitiveUsedContentStreams
                    WHERE
                        cs.contentStreamIdentifier = transitiveUsedContentStreams.contentStreamIdentifier
                )
            ',
            [
                'inUseState' => self::STATE_IN_USE_BY_WORKSPACE
            ]
        )->fetchAll();

        $contentStreams = [];
        foreach ($databaseRows as $databaseRow) {
            $contentStreams[] = ContentStreamIdentifier::fromString($databaseRow['contentStreamIdentifier']);
        }

        return $contentStreams;
    }
}
