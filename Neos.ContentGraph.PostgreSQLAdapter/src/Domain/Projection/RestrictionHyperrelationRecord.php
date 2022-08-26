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
use Neos\ContentRepository\SharedModel\Workspace\ContentStreamIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;

/**
 * The active record for reading and writing restriction hyperrelations from and to the database
 *
 * @internal
 */
final class RestrictionHyperrelationRecord
{
    public ContentStreamIdentifier $contentStreamIdentifier;

    public string $dimensionSpacePointHash;

    public NodeAggregateIdentifier $originNodeAggregateIdentifier;

    public NodeAggregateIdentifiers $affectedNodeAggregateIdentifiers;

    public function __construct(
        ContentStreamIdentifier $contentStreamIdentifier,
        string $dimensionSpacePointHash,
        NodeAggregateIdentifier $originNodeAggregateIdentifier,
        NodeAggregateIdentifiers $affectedNodeAggregateIdentifiers
    ) {
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionSpacePointHash = $dimensionSpacePointHash;
        $this->originNodeAggregateIdentifier = $originNodeAggregateIdentifier;
        $this->affectedNodeAggregateIdentifiers = $affectedNodeAggregateIdentifiers;
    }

    /**
     * @param array<string,string> $databaseRow
     */
    public static function fromDatabaseRow(array $databaseRow): self
    {
        return new self(
            ContentStreamIdentifier::fromString($databaseRow['contentstreamidentifier']),
            $databaseRow['dimensionspacepointhash'],
            NodeAggregateIdentifier::fromString($databaseRow['originnodeaggregateidentifier']),
            NodeAggregateIdentifiers::fromDatabaseString($databaseRow['affectednodeaggregateidentifiers'])
        );
    }

    /**
     * @throws DBALException
     */
    public function addAffectedNodeAggregateIdentifier(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        Connection $databaseConnection,
        string $tableNamePrefix
    ): void {
        $affectedNodeAggregateIdentifiers = $this->affectedNodeAggregateIdentifiers->add($nodeAggregateIdentifier);

        $this->updateAffectedNodeAggregateIdentifiers(
            $affectedNodeAggregateIdentifiers,
            $databaseConnection,
            $tableNamePrefix
        );
    }

    /**
     * @throws DBALException
     */
    public function removeAffectedNodeAggregateIdentifier(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        Connection $databaseConnection,
        string $tableNamePrefix
    ): void {
        $affectedNodeAggregateIdentifiers = $this->affectedNodeAggregateIdentifiers->remove($nodeAggregateIdentifier);
        if ($affectedNodeAggregateIdentifiers->isEmpty()) {
            $this->removeFromDatabase($databaseConnection, $tableNamePrefix);
        } else {
            $this->updateAffectedNodeAggregateIdentifiers(
                $affectedNodeAggregateIdentifiers,
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
                contentstreamidentifier,
                dimensionspacepointhash,
                originnodeaggregateidentifier,
                affectednodeaggregateidentifiers
            ) VALUES (?, ?, ?, ?)
            ON CONFLICT DO NOTHING',
            [
                (string)$this->contentStreamIdentifier,
                $this->dimensionSpacePointHash,
                (string)$this->originNodeAggregateIdentifier,
                $this->affectedNodeAggregateIdentifiers->toDatabaseString()
            ]
        );
    }

    /**
     * @throws DBALException
     */
    private function updateAffectedNodeAggregateIdentifiers(
        NodeAggregateIdentifiers $affectedNodeAggregateIdentifiers,
        Connection $databaseConnection,
        string $tableNamePrefix
    ): void {
        $databaseConnection->update(
            $tableNamePrefix . '_restrictionhyperrelation',
            [
                'affectednodeaggregateidentifiers' => $affectedNodeAggregateIdentifiers->toDatabaseString()
            ],
            $this->getDatabaseIdentifier()
        );
        $this->affectedNodeAggregateIdentifiers = $affectedNodeAggregateIdentifiers;
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
            'contentstreamidentifier' => (string)$this->contentStreamIdentifier,
            'dimensionspacepointhash' => $this->dimensionSpacePointHash,
            'originnodeaggregateidentifier' => (string)$this->originNodeAggregateIdentifier
        ];
    }
}
