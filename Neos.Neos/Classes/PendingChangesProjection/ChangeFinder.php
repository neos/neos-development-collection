<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\Neos\PendingChangesProjection;

use Neos\ContentRepository\Core\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Core\Projection\ProjectionStateInterface;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Annotations as Flow;

/**
 * Finder for changes
 *
 * !!! Still a bit unstable - might change in the future.
 *
 * @Flow\Proxy(false)
 */
final class ChangeFinder implements ProjectionStateInterface
{
    /**
     * @param DbalClientInterface $client
     */
    public function __construct(
        private readonly DbalClientInterface $client,
        private readonly string $tableName
    ) {
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @return array|Change[]
     */
    public function findByContentStreamId(ContentStreamId $contentStreamId): array
    {
        $connection = $this->client->getConnection();
        $changeRows = $connection->executeQuery(
            '
                SELECT * FROM ' . $this->tableName . '
                WHERE contentStreamId = :contentStreamId
            ',
            [
                ':contentStreamId' => (string)$contentStreamId
            ]
        )->fetchAll();
        $changes = [];
        foreach ($changeRows as $changeRow) {
            $changes[] = Change::fromDatabaseRow($changeRow);
        }
        return $changes;
    }

    public function countByContentStreamId(ContentStreamId $contentStreamId): int
    {
        $connection = $this->client->getConnection();
        return (int)$connection->executeQuery(
            '
                SELECT * FROM ' . $this->tableName . '
                WHERE contentStreamId = :contentStreamId
            ',
            [
                ':contentStreamId' => (string)$contentStreamId
            ]
        )->rowCount();
    }
}
