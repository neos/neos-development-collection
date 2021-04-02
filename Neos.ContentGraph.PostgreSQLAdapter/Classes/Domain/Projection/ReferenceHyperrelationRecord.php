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
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateIdentifierCollection;
use Neos\EventSourcedContentRepository\Domain\ValueObject\PropertyName;
use Neos\Flow\Annotations as Flow;

/**
 * The active record for reading and writing reference hyperrelations from and to the database
 *
 * @Flow\Proxy(false)
 */
final class ReferenceHyperrelationRecord
{
    const TABLE_NAME = 'neos_contentgraph_referencehyperrelation';

    public NodeRelationAnchorPoint $originNodeAnchor;

    public PropertyName $name;

    public NodeAggregateIdentifiers $destinationNodeAggregateIdentifiers;

    public function __construct(
        NodeRelationAnchorPoint $originNodeAnchor,
        PropertyName $name,
        NodeAggregateIdentifiers $destinationNodeAggregateIdentifiers
    ) {
        $this->originNodeAnchor = $originNodeAnchor;
        $this->name = $name;
        $this->destinationNodeAggregateIdentifiers = $destinationNodeAggregateIdentifiers;
    }

    public static function fromDatabaseRow(array $databaseRow): self
    {
        return new self(
            NodeRelationAnchorPoint::fromString($databaseRow['originnodeanchor']),
            PropertyName::fromString($databaseRow['name']),
            NodeAggregateIdentifiers::fromDatabaseString($databaseRow['destinationnodeaggregateidentifiers'])
        );
    }

    /**
     * @throws DBALException
     */
    public function addToDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->insert(self::TABLE_NAME, [
            'originnodeanchor' => (string)$this->originNodeAnchor,
            'name' => (string)$this->name,
            'destinationnodeaggregateidentifiers' => $this->destinationNodeAggregateIdentifiers->toDatabaseString()
        ]);
    }
    /**
     * @throws DBALException
     */
    public function setDestinationNodeAggregateIdentifiers(
        NodeAggregateIdentifiers $nodeAggregateIdentifiers,
        Connection $databaseConnection
    ): void {
        $databaseConnection->update(
            self::TABLE_NAME,
            [
                'destinationNodeAggregateIdentifiers' => $nodeAggregateIdentifiers->toDatabaseString()
            ],
            $this->getDatabaseIdentifier()
        );
        $this->destinationNodeAggregateIdentifiers = $nodeAggregateIdentifiers;
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
            'originnodeanchor' => (string)$this->originNodeAnchor,
            'name' => (string)$this->name
        ];
    }
}
