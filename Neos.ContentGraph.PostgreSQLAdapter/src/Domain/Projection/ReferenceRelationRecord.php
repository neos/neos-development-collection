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
use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\PropertyName;

/**
 * The active record for reading and writing reference relations from and to the database
 */
final class ReferenceRelationRecord
{
    public const TABLE_NAME = 'neos_contentgraph_referencerelation';

    public function __construct(
        public readonly NodeRelationAnchorPoint $originNodeAnchor,
        public readonly PropertyName $name,
        public readonly int $position,
        public readonly ?SerializedPropertyValues $properties,
        public readonly NodeAggregateIdentifier $targetNodeAggregateIdentifier
    ) {
    }

    /**
     * @param array<string,mixed> $databaseRow
     */
    public static function fromDatabaseRow(array $databaseRow): self
    {
        return new self(
            NodeRelationAnchorPoint::fromString($databaseRow['originnodeanchor']),
            PropertyName::fromString($databaseRow['name']),
            $databaseRow['position'],
            $databaseRow['properties']
                ? SerializedPropertyValues::fromJsonString($databaseRow['properties'])
                : null,
            NodeAggregateIdentifier::fromString($databaseRow['targetnodeaggregateidentifier'])
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
            'position' => $this->position,
            'properties' => $this->properties
                ? \json_encode($this->properties)
                : null,
            'targetnodeaggregateidentifier' => (string)$this->targetNodeAggregateIdentifier
        ]);
    }

    public function withOriginNodeAnchor(NodeRelationAnchorPoint $originNodeAnchor): self
    {
        return new self(
            $originNodeAnchor,
            $this->name,
            $this->position,
            $this->properties,
            $this->targetNodeAggregateIdentifier
        );
    }

    public static function removeFromDatabaseForSource(
        NodeRelationAnchorPoint $sourceNodeAnchor,
        Connection $databaseConnection
    ): void {
        $databaseConnection->delete(self::TABLE_NAME, [
            'sourcenodeanchor' => $sourceNodeAnchor
        ]);
    }
}
