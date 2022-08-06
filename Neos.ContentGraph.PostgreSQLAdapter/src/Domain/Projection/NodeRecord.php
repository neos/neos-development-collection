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
use Neos\ContentRepository\SharedModel\Node\NodeAggregateClassification;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodeName;
use Neos\ContentRepository\SharedModel\NodeType\NodeTypeName;
use Neos\ContentRepository\SharedModel\Node\OriginDimensionSpacePoint;
use Neos\ContentRepository\Feature\Common\SerializedPropertyValues;

/**
 * The active record for reading and writing nodes from and to the database
 */
final class NodeRecord
{
    public const TABLE_NAME = 'neos_contentgraph_node';

    public function __construct(
        public NodeRelationAnchorPoint $relationAnchorPoint,
        public NodeAggregateIdentifier $nodeAggregateIdentifier,
        public OriginDimensionSpacePoint $originDimensionSpacePoint,
        public string $originDimensionSpacePointHash,
        public SerializedPropertyValues $properties,
        public NodeTypeName $nodeTypeName,
        public NodeAggregateClassification $classification,
        public ?NodeName $nodeName = null
    ) {
    }

    /**
     * @param array<string,string> $databaseRow
     * @throws \Exception
     */
    public static function fromDatabaseRow(array $databaseRow): self
    {
        return new self(
            NodeRelationAnchorPoint::fromString($databaseRow['relationanchorpoint']),
            NodeAggregateIdentifier::fromString($databaseRow['nodeaggregateidentifier']),
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
    public function addToDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->insert(self::TABLE_NAME, [
            'relationanchorpoint' => (string) $this->relationAnchorPoint,
            'origindimensionspacepoint' => json_encode($this->originDimensionSpacePoint),
            'origindimensionspacepointhash' => $this->originDimensionSpacePoint->hash,
            'nodeaggregateidentifier' => (string) $this->nodeAggregateIdentifier,
            'nodetypename' => (string) $this->nodeTypeName,
            'classification' => $this->classification->value,
            'properties' => json_encode($this->properties),
            'nodename' => (string) $this->nodeName
        ]);
    }

    /**
     * @throws DBALException
     */
    public function updateToDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->update(
            self::TABLE_NAME,
            [
                'origindimensionspacepoint' => json_encode($this->originDimensionSpacePoint),
                'origindimensionspacepointhash' => $this->originDimensionSpacePoint->hash,
                'nodeaggregateidentifier' => (string) $this->nodeAggregateIdentifier,
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
    public function removeFromDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->delete(self::TABLE_NAME, [
            'relationanchorpoint' => $this->relationAnchorPoint
        ]);
    }
}
