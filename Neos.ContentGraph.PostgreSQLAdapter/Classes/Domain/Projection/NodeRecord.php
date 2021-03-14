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

    /**
     * Transient node name to store a node name after fetching a node with hierarchy (not always available)
     */
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
     * @throws \Exception
     */
    public static function fromDatabaseRow(array $databaseRow): self
    {
        return new self(
            NodeRelationAnchorPoint::fromString($databaseRow['relationanchorpoint']),
            NodeAggregateIdentifier::fromString($databaseRow['nodeaggregateidentifier']),
            OriginDimensionSpacePoint::fromJsonString($databaseRow['origindimensionspacepoint']),
            $databaseRow['origindimensionspacepointhash'],
            SerializedPropertyValues::fromArray(json_decode($databaseRow['properties'], true)),
            NodeTypeName::fromString($databaseRow['nodetypename']),
            NodeAggregateClassification::fromString($databaseRow['classification']),
            isset($databaseRow['name']) ? NodeName::fromString($databaseRow['name']) : null
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
            'origindimensionspacepointhash' => $this->originDimensionSpacePoint->getHash(),
            'nodeaggregateidentifier' => (string) $this->nodeAggregateIdentifier,
            'nodetypename' => (string) $this->nodeTypeName,
            'classification' => (string) $this->classification,
            'properties' => json_encode($this->properties)
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
                'origindimensionspacepointhash' => $this->originDimensionSpacePoint->getHash(),
                'nodeaggregateidentifier' => (string) $this->nodeAggregateIdentifier,
                'nodetypename' => (string) $this->nodeTypeName,
                'classification' => (string) $this->classification,
                'properties' => json_encode($this->properties)
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
        $databaseConnection->delete('neos_contentgraph_node', [
            'relationanchorpoint' => $this->relationAnchorPoint
        ]);
    }
}
