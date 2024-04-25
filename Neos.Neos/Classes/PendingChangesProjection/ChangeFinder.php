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
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Annotations as Flow;

/**
 * Finder for changes
 *
 * !!! Still a bit unstable - might change in the future.
 */
#[Flow\Proxy(false)]
final readonly class ChangeFinder implements ProjectionStateInterface
{
    public function __construct(
        private DbalClientInterface $client,
        private string $tableName,
        public string $ancestryTableName,
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
                ':contentStreamId' => $contentStreamId->value
            ]
        )->fetchAllAssociative();
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
                ':contentStreamId' => $contentStreamId->value
            ]
        )->rowCount();
    }

    /**
     * @param ContentStreamId $contentStreamId
     * @return array|Change[]
     */
    public function findBySiteAndContentStreamId(
        NodeAggregateId $siteId,
        ContentStreamId $contentStreamId
    ): array {
        $connection = $this->client->getConnection();
        $changeRows = $connection->executeQuery(
            '
                SELECT * FROM ' . $this->tableName . ' c
                JOIN ' . $this->ancestryTableName . ' a
                  ON c.contentStreamId = a.contentStreamId
                  AND c.nodeaggregateid = a.nodeaggregateid
                  AND c.origindimensionspacepointhash = a.dimensionspacepointhash
                WHERE c.contentStreamId = :contentStreamId
                    AND a.sitenodeaggregateid = :siteNodeAggregateId
            ',
            [
                ':contentStreamId' => $contentStreamId->value
            ]
        )->fetchAllAssociative();
        $changes = [];
        foreach ($changeRows as $changeRow) {
            $changes[] = Change::fromDatabaseRow($changeRow);
        }
        return $changes;
    }

    public function countBySiteAndContentStreamId(
        NodeAggregateId $siteId,
        ContentStreamId $contentStreamId
    ): int {
        $connection = $this->client->getConnection();
        return (int)$connection->executeQuery(
            '
                SELECT * FROM ' . $this->tableName . ' c
                JOIN ' . $this->ancestryTableName . ' a
                  ON c.contentStreamId = a.contentStreamId
                  AND c.nodeaggregateid = a.nodeaggregateid
                  AND c.origindimensionspacepointhash = a.dimensionspacepointhash
                WHERE c.contentStreamId = :contentStreamId
                    AND a.sitenodeaggregateid = :siteNodeAggregateId
            ',
            [
                'contentStreamId' => $contentStreamId->value,
                'siteNodeAggregateId' => $siteId->value,
            ]
        )->rowCount();
    }

    public function countByDocumentAndContentStreamId(
        NodeAggregateId $documentId,
        ContentStreamId $contentStreamId
    ): int {
        $connection = $this->client->getConnection();
        return (int)$connection->executeQuery(
            '
                SELECT * FROM ' . $this->tableName . ' c
                JOIN ' . $this->ancestryTableName . ' a
                  ON c.contentStreamId = a.contentStreamId
                  AND c.nodeaggregateid = a.nodeaggregateid
                  AND c.origindimensionspacepointhash = a.dimensionspacepointhash
                WHERE c.contentStreamId = :contentStreamId
                    AND c.documentnodeaggregateid = :documentNodeAggregateId
            ',
            [
                'contentStreamId' => $contentStreamId->value,
                'documentNodeAggregateId' => $documentId->value,
            ]
        )->rowCount();
    }
}
