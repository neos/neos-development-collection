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
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateClassification;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\OriginDimensionSpacePoint;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\Flow\Annotations as Flow;

/**
 * The active record for reading and writing nodes from and to the database
 *
 * @Flow\Proxy(false)
 */
final class NodeRecord
{
    const TABLE_NAME = 'neos_contentgraph_node';

    public NodeRelationAnchorPoint $relationAnchorPoint;

    public NodeAggregateIdentifier $nodeAggregateIdentifier;

    public OriginDimensionSpacePoint $originDimensionSpacePoint;

    public string $originDimensionSpacePointHash;

    public SerializedPropertyValues $properties;

    public NodeTypeName $nodeTypeName;

    public NodeAggregateClassification $classification;

    public ?NodeName $nodeName;

    public function __construct(
        NodeRelationAnchorPoint $relationAnchorPoint,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        OriginDimensionSpacePoint $originDimensionSpacePoint,
        string $originDimensionSpacePointHash,
        SerializedPropertyValues $properties,
        NodeTypeName $nodeTypeName,
        NodeAggregateClassification $classification,
        ?NodeName $nodeName = null
    ) {
        $this->relationAnchorPoint = $relationAnchorPoint;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
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
