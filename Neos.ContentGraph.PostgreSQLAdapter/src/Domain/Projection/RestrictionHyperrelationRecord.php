<?php

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * The active record for reading and writing restriction hyperrelations from and to the database
 *
 * @internal
 */
final class RestrictionHyperrelationRecord
{
    public ContentStreamId $contentStreamId;

    public string $dimensionSpacePointHash;

    public NodeAggregateId $originNodeAggregateId;

    public NodeAggregateIds $affectedNodeAggregateIds;

    public function __construct(
        ContentStreamId $contentStreamId,
        string $dimensionSpacePointHash,
        NodeAggregateId $originNodeAggregateId,
        NodeAggregateIds $affectedNodeAggregateIds
    ) {
        $this->contentStreamId = $contentStreamId;
        $this->dimensionSpacePointHash = $dimensionSpacePointHash;
        $this->originNodeAggregateId = $originNodeAggregateId;
        $this->affectedNodeAggregateIds = $affectedNodeAggregateIds;
    }

    /**
     * @param array<string,string> $databaseRow
     */
    public static function fromDatabaseRow(array $databaseRow): self
    {
        return new self(
            ContentStreamId::fromString($databaseRow['contentstreamid']),
            $databaseRow['dimensionspacepointhash'],
            NodeAggregateId::fromString($databaseRow['originnodeaggregateid']),
            NodeAggregateIds::fromDatabaseString($databaseRow['affectednodeaggregateids'])
        );
    }

    /**
     * @throws DBALException
     */
    public function addAffectedNodeAggregateId(
        NodeAggregateId $nodeAggregateId,
        Connection $databaseConnection,
        string $tableNamePrefix
    ): void {
        $affectedNodeAggregateIds = $this->affectedNodeAggregateIds->add($nodeAggregateId);

        $this->updateAffectedNodeAggregateIds(
            $affectedNodeAggregateIds,
            $databaseConnection,
            $tableNamePrefix
        );
    }

    /**
     * @throws DBALException
     */
    public function removeAffectedNodeAggregateId(
        NodeAggregateId $nodeAggregateId,
        Connection $databaseConnection,
        string $tableNamePrefix
    ): void {
        $affectedNodeAggregateIds = $this->affectedNodeAggregateIds->remove($nodeAggregateId);
        if ($affectedNodeAggregateIds->isEmpty()) {
            $this->removeFromDatabase($databaseConnection, $tableNamePrefix);
        } else {
            $this->updateAffectedNodeAggregateIds(
                $affectedNodeAggregateIds,
                $databaseConnection,
                $tableNamePrefix
            );
        }
    }

    /**
     * @throws DBALException
     */
    public function addToDatabase(Connection $databaseConnection, string $tableNamePrefix): void
    {
        $databaseConnection->executeStatement(
            'INSERT INTO ' . $tableNamePrefix . '_restrictionhyperrelation (
                contentstreamid,
                dimensionspacepointhash,
                originnodeaggregateid,
                affectednodeaggregateids
            ) VALUES (?, ?, ?, ?)
            ON CONFLICT DO NOTHING',
            [
                (string)$this->contentStreamId,
                $this->dimensionSpacePointHash,
                (string)$this->originNodeAggregateId,
                $this->affectedNodeAggregateIds->toDatabaseString()
            ]
        );
    }

    /**
     * @throws DBALException
     */
    private function updateAffectedNodeAggregateIds(
        NodeAggregateIds $affectedNodeAggregateIds,
        Connection $databaseConnection,
        string $tableNamePrefix
    ): void {
        $databaseConnection->update(
            $tableNamePrefix . '_restrictionhyperrelation',
            [
                'affectednodeaggregateids' => $affectedNodeAggregateIds->toDatabaseString()
            ],
            $this->getDatabaseIdentifier()
        );
        $this->affectedNodeAggregateIds = $affectedNodeAggregateIds;
    }

    /**
     * @throws DBALException
     */
    public function removeFromDatabase(Connection $databaseConnection, string $tableNamePrefix): void
    {
        $databaseConnection->delete($tableNamePrefix . '_restrictionhyperrelation', $this->getDatabaseIdentifier());
    }

    /**
     * @return array<string,string>
     */
    public function getDatabaseIdentifier(): array
    {
        return [
            'contentstreamid' => (string)$this->contentStreamId,
            'dimensionspacepointhash' => $this->dimensionSpacePointHash,
            'originnodeaggregateid' => (string)$this->originNodeAggregateId
        ];
    }
}
