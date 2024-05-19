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

use Doctrine\DBAL\Connection;
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
    public function __construct(
        private readonly Connection $dbal,
        private readonly string $tableName,
    ) {
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @return array|Change[]
     */
    public function findByContentStreamId(ContentStreamId $contentStreamId): array
    {
        $changeRows = $this->dbal->executeQuery(
            '
                SELECT * FROM ' . $this->tableName . '
                WHERE contentStreamId = :contentStreamId
            ',
            [
                ':contentStreamId' => $contentStreamId->value
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
        return (int)$this->dbal->executeQuery(
            '
                SELECT * FROM ' . $this->tableName . '
                WHERE contentStreamId = :contentStreamId
            ',
            [
                ':contentStreamId' => $contentStreamId->value
            ]
        )->rowCount();
    }
}
