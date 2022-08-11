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

use Neos\ContentRepository\Infrastructure\DbalClientInterface;
use Neos\ContentRepository\Projection\ProjectionStateInterface;
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
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
     * @param ContentStreamIdentifier $contentStreamIdentifier
     * @return array|Change[]
     */
    public function findByContentStreamIdentifier(ContentStreamIdentifier $contentStreamIdentifier): array
    {
        $connection = $this->client->getConnection();
        $changeRows = $connection->executeQuery(
            '
                SELECT * FROM ' . $this->tableName . '
                WHERE contentStreamIdentifier = :contentStreamIdentifier
            ',
            [
                ':contentStreamIdentifier' => (string)$contentStreamIdentifier
            ]
        )->fetchAll();
        $changes = [];
        foreach ($changeRows as $changeRow) {
            $changes[] = Change::fromDatabaseRow($changeRow);
        }
        return $changes;
    }

    public function countByContentStreamIdentifier(ContentStreamIdentifier $contentStreamIdentifier): int
    {
        $connection = $this->client->getConnection();
        return (int)$connection->executeQuery(
            '
                SELECT * FROM ' . $this->tableName . '
                WHERE contentStreamIdentifier = :contentStreamIdentifier
            ',
            [
                ':contentStreamIdentifier' => (string)$contentStreamIdentifier
            ]
        )->rowCount();
    }
}
