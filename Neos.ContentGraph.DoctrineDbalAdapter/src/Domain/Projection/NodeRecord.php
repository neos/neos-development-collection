<?php

/*
 * This file is part of the Neos.ContentGraph package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;

/**
 * The active record for reading and writing nodes from and to the database
 *
 * @internal
 */
final class NodeRecord
{
    public function __construct(
        public NodeRelationAnchorPoint $relationAnchorPoint,
        public NodeAggregateId $nodeAggregateId,
        /** @var array<string,string> */
        public array $originDimensionSpacePoint,
        public string $originDimensionSpacePointHash,
        public SerializedPropertyValues $properties,
        public NodeTypeName $nodeTypeName,
        public NodeAggregateClassification $classification,
        /** Transient node name to store a node name after fetching a node with hierarchy (not always available) */
        public ?NodeName $nodeName = null
    ) {
    }

    /**
     * @param Connection $databaseConnection
     * @throws \Doctrine\DBAL\DBALException
     */
    public function addToDatabase(Connection $databaseConnection, string $tableNamePrefix): void
    {
        $databaseConnection->insert($tableNamePrefix . '_node', [
            'relationanchorpoint' => (string)$this->relationAnchorPoint,
            'nodeaggregateid' => (string)$this->nodeAggregateId,
            'origindimensionspacepoint' => json_encode($this->originDimensionSpacePoint),
            'origindimensionspacepointhash' => $this->originDimensionSpacePointHash,
            'properties' => json_encode($this->properties),
            'nodetypename' => (string)$this->nodeTypeName,
            'classification' => $this->classification->value
        ]);
    }

    /**
     * @param Connection $databaseConnection
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updateToDatabase(Connection $databaseConnection, string $tableNamePrefix): void
    {
        $databaseConnection->update(
            $tableNamePrefix . '_node',
            [
                'nodeaggregateid' => (string)$this->nodeAggregateId,
                'origindimensionspacepoint' => json_encode($this->originDimensionSpacePoint),
                'origindimensionspacepointhash' => $this->originDimensionSpacePointHash,
                'properties' => json_encode($this->properties),
                'nodetypename' => (string)$this->nodeTypeName,
                'classification' => $this->classification->value
            ],
            [
                'relationanchorpoint' => $this->relationAnchorPoint
            ]
        );
    }

    /**
     * @param Connection $databaseConnection
     * @throws \Doctrine\DBAL\DBALException
     * @throws \Doctrine\DBAL\Exception\InvalidArgumentException
     */
    public function removeFromDatabase(Connection $databaseConnection, string $tableNamePrefix): void
    {
        $databaseConnection->delete($tableNamePrefix . '_node', [
            'relationanchorpoint' => $this->relationAnchorPoint
        ]);
    }

    /**
     * @param array<string,string> $databaseRow
     * @throws \Exception
     */
    public static function fromDatabaseRow(array $databaseRow): self
    {
        return new self(
            NodeRelationAnchorPoint::fromString($databaseRow['relationanchorpoint']),
            NodeAggregateId::fromString($databaseRow['nodeaggregateid']),
            json_decode($databaseRow['origindimensionspacepoint'], true),
            $databaseRow['origindimensionspacepointhash'],
            SerializedPropertyValues::fromArray(json_decode($databaseRow['properties'], true)),
            NodeTypeName::fromString($databaseRow['nodetypename']),
            NodeAggregateClassification::from($databaseRow['classification']),
            isset($databaseRow['name']) ? NodeName::fromString($databaseRow['name']) : null
        );
    }
}
