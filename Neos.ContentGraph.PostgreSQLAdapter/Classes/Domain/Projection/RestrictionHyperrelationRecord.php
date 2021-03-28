<?php
declare(strict_types=1);

namespace Neos\ContentGraph\PostgreSQLAdapter\Domain\Projection;

/*
 * This file is part of the Neos.ContentGraph.PostgreSQLAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception as DBALException;
use Neos\ContentRepository\Domain\ContentStream\ContentStreamIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\Flow\Annotations as Flow;

/**
 * The active record for reading and writing restriction hyperrelations from and to the database
 *
 * @Flow\Proxy(false)
 */
final class RestrictionHyperrelationRecord
{
    const TABLE_NAME = 'neos_contentgraph_restrictionhyperrelation';

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

    public static function fromDatabaseRow(array $databaseRow): self
    {
        return new self(
            ContentStreamIdentifier::fromString($databaseRow['contentstreamidentifier']),
            $databaseRow['dimensionspacepointhash'],
            NodeAggregateIdentifier::fromString($databaseRow['originnodeaggregateidentifier']),
            NodeAggregateIdentifiers::fromDatabaseString($databaseRow['affectednodeaggregateidentifiers'])
        );
    }

    public function addAffectedNodeAggregateIdentifier(
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        Connection $databaseConnection
    ): void {
        $affectedNodeAggregateIdentifiers = $this->affectedNodeAggregateIdentifiers->add($nodeAggregateIdentifier);

        $databaseConnection->update(
            self::TABLE_NAME,
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
    public function addToDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->insert(self::TABLE_NAME, [
            'contentstreamidentifier' => (string)$this->contentStreamIdentifier,
            'dimensionspacepointhash' => $this->dimensionSpacePointHash,
            'originnodeaggregateidentifier' => (string)$this->originNodeAggregateIdentifier,
            'affectednodeaggregateidentifiers' => $this->affectedNodeAggregateIdentifiers->toDatabaseString()
        ]);
    }

    /**
     * @throws DBALException
     */
    public function removeFromDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->delete(self::TABLE_NAME, $this->getDatabaseIdentifier());
    }

    public function getDatabaseIdentifier(): array
    {
        return [
            'contentstreamidentifier' => (string)$this->contentStreamIdentifier,
            'dimensionspacepointhash' => $this->dimensionSpacePointHash,
            'originnodeaggregateidentifier' => (string)$this->originNodeAggregateIdentifier
        ];
    }
}
