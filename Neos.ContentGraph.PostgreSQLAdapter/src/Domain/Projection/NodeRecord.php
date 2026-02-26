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
use Neos\ContentGraph\PostgreSQLAdapter\ContentGraphTableNames;
use Neos\ContentRepository\Core\DimensionSpace\OriginDimensionSpacePoint;
use Neos\ContentRepository\Core\Feature\NodeModification\Dto\SerializedPropertyValues;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;

/**
 * The active record for reading and writing nodes from and to the database
 *
 * @internal
 */
final class NodeRecord
{
    public ?NodeRelationAnchorPoint $relationAnchorPoint = null;

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
     * @throws DBALException
     */
    public function addToDatabase(Connection $databaseConnection, ContentGraphTableNames $tableNames): void
    {
        $result = $databaseConnection->executeQuery(
            'INSERT INTO ' . $tableNames->node() . ' (
                nodeaggregateid,
                origindimensionspacepoint,
                origindimensionspacepointhash,
                nodetypename,
                properties,
                classification,
                nodename
            ) VALUES (
                :nodeaggregateid,
                :origindimensionspacepoint,
                :origindimensionspacepointhash,
                :nodetypename,
                :properties,
                :classification,
                :nodename
            )
            RETURNING relationanchorpoint',
            [
                'nodeaggregateid' => $this->nodeAggregateId->value,
                'origindimensionspacepoint' => $this->originDimensionSpacePoint->toJson(),
                'origindimensionspacepointhash' => $this->originDimensionSpacePointHash,
                'nodetypename' => $this->nodeTypeName->value,
                'properties' => json_encode($this->properties),
                'classification' => $this->classification->value,
                'nodename' => $this->nodeName?->value ?? '',
            ]
        );

        $row = $result->fetchAssociative();
        if ($row !== false) {
            $this->relationAnchorPoint = NodeRelationAnchorPoint::fromInteger($row['relationanchorpoint']);
        }
    }

    /**
     * @throws DBALException
     */
    public function updateToDatabase(Connection $databaseConnection, ContentGraphTableNames $tableNames): void
    {
        $databaseConnection->update(
            $tableNames->node(),
            [
                'nodeaggregateid' => $this->nodeAggregateId->value,
                'origindimensionspacepoint' => $this->originDimensionSpacePoint->toJson(),
                'origindimensionspacepointhash' => $this->originDimensionSpacePointHash,
                'properties' => json_encode($this->properties),
                'nodetypename' => $this->nodeTypeName->value,
                'classification' => $this->classification->value,
                'nodename' => $this->nodeName?->value ?? '',
            ],
            [
                'relationanchorpoint' => $this->relationAnchorPoint->value,
            ]
        );
    }

    /**
     * @param array<string,mixed> $databaseRow
     * @throws \Exception
     */
    public static function fromDatabaseRow(array $databaseRow): self
    {
        return new self(
            NodeRelationAnchorPoint::fromInteger($databaseRow['relationanchorpoint']),
            NodeAggregateId::fromString($databaseRow['nodeaggregateid']),
            OriginDimensionSpacePoint::fromJsonString($databaseRow['origindimensionspacepoint']),
            $databaseRow['origindimensionspacepointhash'],
            SerializedPropertyValues::fromJsonString($databaseRow['properties']),
            NodeTypeName::fromString($databaseRow['nodetypename']),
            NodeAggregateClassification::from($databaseRow['classification']),
            $databaseRow['nodename'] ? NodeName::fromString($databaseRow['nodename']) : null
        );
    }

}
