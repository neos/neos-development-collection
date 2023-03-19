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
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;

/**
 * The active record for reading and writing nodes from and to the database
 *
 * @internal
 */
final class NodeRecord
{
    public NodeRelationAnchorPoint $relationAnchorPoint;

    public NodeAggregateId $nodeAggregateId;

    public OriginDimensionSpacePoint $originDimensionSpacePoint;

    public string $originDimensionSpacePointHash;

    public SerializedPropertyValues $properties;

    public NodeTypeName $nodeTypeName;

    public NodeAggregateClassification $classification;

    public ?NodeName $nodeName;

    public function __construct(
        NodeRelationAnchorPoint $relationAnchorPoint,
        NodeAggregateId $nodeAggregateId,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        string $originDimensionSpacePointHash,
        SerializedPropertyValues $properties,
        NodeTypeName $nodeTypeName,
        NodeAggregateClassification $classification,
        ?NodeName $nodeName = null
    ) {
        $this->relationAnchorPoint = $relationAnchorPoint;
        $this->nodeAggregateId = $nodeAggregateId;
        $this->originDimensionSpacePoint = $originDimensionSpacePoint;
        $this->originDimensionSpacePointHash = $originDimensionSpacePointHash;
        $this->properties = $properties;
        $this->nodeTypeName = $nodeTypeName;
        $this->classification = $classification;
        $this->nodeName = $nodeName;
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
            OriginDimensionSpacePoint::fromJsonString($databaseRow['origindimensionspacepoint']),
            $databaseRow['origindimensionspacepointhash'],
            SerializedPropertyValues::fromJsonString($databaseRow['properties']),
            NodeTypeName::fromString($databaseRow['nodetypename']),
            NodeAggregateClassification::from($databaseRow['classification']),
            $databaseRow['nodename'] ? NodeName::fromString($databaseRow['nodename']) : null
        );
    }

    /**
     * @throws DBALException
     */
    public function addToDatabase(Connection $databaseConnection, string $tableNamePrefix): void
    {
        $databaseConnection->insert($tableNamePrefix . '_node', [
            'relationanchorpoint' => (string) $this->relationAnchorPoint,
            'origindimensionspacepoint' => json_encode($this->originDimensionSpacePoint),
            'origindimensionspacepointhash' => $this->originDimensionSpacePoint->hash,
            'nodeaggregateid' => (string) $this->nodeAggregateId,
            'nodetypename' => (string) $this->nodeTypeName,
            'classification' => $this->classification->value,
            'properties' => json_encode($this->properties),
            'nodename' => (string) $this->nodeName
        ]);
    }

    /**
     * @throws DBALException
     */
    public function updateToDatabase(Connection $databaseConnection, string $tableNamePrefix): void
    {
        $databaseConnection->update(
            $tableNamePrefix . '_node',
            [
                'origindimensionspacepoint' => json_encode($this->originDimensionSpacePoint),
                'origindimensionspacepointhash' => $this->originDimensionSpacePoint->hash,
                'nodeaggregateid' => (string) $this->nodeAggregateId,
                'nodetypename' => (string) $this->nodeTypeName,
                'classification' => $this->classification->value,
                'properties' => json_encode($this->properties),
                'nodename' => (string) $this->nodeName,
            ],
            [
                'relationanchorpoint' => $this->relationAnchorPoint
            ]
        );
    }

    /**
     * @throws DBALException
     */
    public function removeFromDatabase(Connection $databaseConnection, string $tableNamePrefix): void
    {
        $databaseConnection->delete($tableNamePrefix . '_node', [
            'relationanchorpoint' => $this->relationAnchorPoint
        ]);
    }
}
