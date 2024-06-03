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
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\ReferenceName;

/**
 * The active record for reading and writing reference relations from and to the database
 *
 * @internal
 */
final readonly class ReferenceRelationRecord
{
    public function __construct(
        public NodeRelationAnchorPoint $sourceNodeAnchor,
        public ReferenceName $name,
        public int $position,
        public ?SerializedPropertyValues $properties,
        public NodeAggregateId $targetNodeAggregateId
    ) {
    }

    /**
     * @param array<string,mixed> $databaseRow
     */
    public static function fromDatabaseRow(array $databaseRow): self
    {
        return new self(
            NodeRelationAnchorPoint::fromString($databaseRow['sourcenodeanchor']),
            ReferenceName::fromString($databaseRow['name']),
            $databaseRow['position'],
            $databaseRow['properties']
                ? SerializedPropertyValues::fromJsonString($databaseRow['properties'])
                : null,
            NodeAggregateId::fromString($databaseRow['targetnodeaggregateid'])
        );
    }

    /**
     * @throws DBALException
     */
    public function addToDatabase(Connection $databaseConnection, string $tableNamePrefix): void
    {
        $databaseConnection->insert($tableNamePrefix . '_referencerelation', [
            'sourcenodeanchor' => $this->sourceNodeAnchor->value,
            'name' => $this->name->value,
            'position' => $this->position,
            'properties' => $this->properties
                ? \json_encode($this->properties)
                : null,
            'targetnodeaggregateid' => $this->targetNodeAggregateId->value
        ]);
    }

    public function withSourceNodeAnchor(NodeRelationAnchorPoint $sourceNodeAnchor): self
    {
        return new self(
            $sourceNodeAnchor,
            $this->name,
            $this->position,
            $this->properties,
            $this->targetNodeAggregateId
        );
    }

    public static function removeFromDatabaseForSource(
        NodeRelationAnchorPoint $sourceNodeAnchor,
        Connection $databaseConnection,
        string $tableNamePrefix
    ): void {
        $databaseConnection->delete($tableNamePrefix . '_referencerelation', [
            'sourcenodeanchor' => $sourceNodeAnchor->value
        ]);
    }
}
