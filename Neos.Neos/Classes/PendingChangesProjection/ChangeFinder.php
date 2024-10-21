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

    public function findByContentStreamId(ContentStreamId $contentStreamId): Changes
    {
        $changeRows = $this->dbal->executeQuery(
            <<<SQL
                SELECT * FROM {$this->tableName}
                WHERE contentStreamId = :contentStreamId
            SQL,
            [
                'contentStreamId' => $contentStreamId->value
            ]
        )->fetchAllAssociative();
        return Changes::fromArray(array_map(Change::fromDatabaseRow(...), $changeRows));
    }

    public function countByContentStreamId(ContentStreamId $contentStreamId): int
    {
        return (int)$this->dbal->fetchOne(
            <<<SQL
                SELECT COUNT(*) FROM {$this->tableName}
                WHERE contentStreamId = :contentStreamId
            SQL,
            [
                'contentStreamId' => $contentStreamId->value
            ]
        );
    }
}
