<?php
declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

/*
 * This file is part of the Neos.ContentGraph package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */
use Doctrine\DBAL\Connection;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;

/**
 * The active record for reading and writing nodes from and to the database
 */
class NodeRecord
{
    /**
     * @var NodeRelationAnchorPoint
     */
    public $relationAnchorPoint;

    /**
     * @var NodeAggregateIdentifier
     */
    public $nodeAggregateIdentifier;

    /**
     * @var array
     */
    public $originDimensionSpacePoint;

    /**
     * @var string
     */
    public $originDimensionSpacePointHash;

    /**
     * @var array
     */
    public $properties;

    /**
     * @var NodeTypeName
     */
    public $nodeTypeName;

    /**
     * Transient node name to store a node name after fetching a node with hierarchy (not always available)
     *
     * @var NodeName
     */
    public $nodeName;

    public function __construct(
        NodeRelationAnchorPoint $relationAnchorPoint,
        NodeAggregateIdentifier $nodeAggregateIdentifier,
        ?array $originDimensionSpacePoint,
        ?string $originDimensionSpacePointHash,
        ?array $properties,
        NodeTypeName $nodeTypeName,
        NodeName $nodeName = null
    ) {
        $this->relationAnchorPoint = $relationAnchorPoint;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->originDimensionSpacePoint = $originDimensionSpacePoint;
        $this->originDimensionSpacePointHash = $originDimensionSpacePointHash;
        $this->properties = $properties;
        $this->nodeTypeName = $nodeTypeName;
        $this->nodeName = $nodeName;
    }

    /**
     * @param Connection $databaseConnection
     * @throws \Doctrine\DBAL\DBALException
     */
    public function addToDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->insert('neos_contentgraph_node', [
            'relationanchorpoint' => (string) $this->relationAnchorPoint,
            'nodeaggregateidentifier' => (string) $this->nodeAggregateIdentifier,
            'origindimensionspacepoint' => json_encode($this->originDimensionSpacePoint),
            'origindimensionspacepointhash' => (string) $this->originDimensionSpacePointHash,
            'properties' => json_encode($this->properties),
            'nodetypename' => (string) $this->nodeTypeName
        ]);
    }

    /**
     * @param Connection $databaseConnection
     * @throws \Doctrine\DBAL\DBALException
     */
    public function updateToDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->update(
            'neos_contentgraph_node',
            [
                'nodeaggregateidentifier' => (string) $this->nodeAggregateIdentifier,
                'origindimensionspacepoint' => json_encode($this->originDimensionSpacePoint),
                'origindimensionspacepointhash' => (string) $this->originDimensionSpacePointHash,
                'properties' => json_encode($this->properties),
                'nodetypename' => (string) $this->nodeTypeName
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
    public function removeFromDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->delete('neos_contentgraph_node', [
            'relationanchorpoint' => $this->relationAnchorPoint
        ]);
    }

    /**
     * @param array $databaseRow
     * @return static
     * @throws \Exception
     */
    public static function fromDatabaseRow(array $databaseRow): NodeRecord
    {
        return new static(
            NodeRelationAnchorPoint::fromString($databaseRow['relationanchorpoint']),
            NodeAggregateIdentifier::fromString($databaseRow['nodeaggregateidentifier']),
            json_decode($databaseRow['origindimensionspacepoint'], true),
            $databaseRow['origindimensionspacepointhash'],
            json_decode($databaseRow['properties'], true),
            NodeTypeName::fromString($databaseRow['nodetypename']),
            isset($databaseRow['name']) ? NodeName::fromString($databaseRow['name']) : null
        );
    }
}
