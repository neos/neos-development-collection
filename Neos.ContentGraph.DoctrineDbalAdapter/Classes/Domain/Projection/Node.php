<?php

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
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeName;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\Flow\Annotations as Flow;

/**
 * The active record for reading and writing nodes from and to the database
 */
class Node
{
    /**
     * @var NodeRelationAnchorPoint
     */
    public $relationAnchorPoint;

    /**
     * @var NodeIdentifier
     */
    public $nodeIdentifier;

    /**
     * @var NodeAggregateIdentifier
     */
    public $nodeAggregateIdentifier;

    /**
     * @var array
     */
    public $dimensionSpacePoint;

    /**
     * @var string
     */
    public $dimensionSpacePointHash;

    /**
     * @var array
     */
    public $properties;

    /**
     * @var NodeTypeName
     */
    public $nodeTypeName;

    /**
     * @var bool
     */
    public $hidden;

    /**
     * Transient node name to store a node name after fetching a node with hierarchy (not always available)
     *
     * @var NodeName
     */
    public $nodeName;

    /**
     * Node constructor.
     *
     * @param NodeRelationAnchorPoint $relationAnchorPoint
     * @param NodeIdentifier $nodeIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param array $dimensionSpacePoint
     * @param string $dimensionSpacePointHash
     * @param array $properties
     * @param NodeTypeName $nodeTypeName
     * @param bool $hidden
     * @param NodeName $nodeName
     */
    public function __construct(
        NodeRelationAnchorPoint $relationAnchorPoint,
        NodeIdentifier $nodeIdentifier,
        ?NodeAggregateIdentifier $nodeAggregateIdentifier,
        ?array $dimensionSpacePoint,
        ?string $dimensionSpacePointHash,
        ?array $properties,
        NodeTypeName $nodeTypeName,
        bool $hidden = false,
        NodeName $nodeName = null
    ) {
        $this->relationAnchorPoint = $relationAnchorPoint;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->dimensionSpacePointHash = $dimensionSpacePointHash;
        $this->properties = $properties;
        $this->nodeTypeName = $nodeTypeName;
        $this->nodeName = $nodeName;
        $this->hidden = $hidden;
    }

    /**
     * @param Connection $databaseConnection
     */
    public function addToDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->insert('neos_contentgraph_node', [
            'relationanchorpoint' => (string) $this->relationAnchorPoint,
            'nodeaggregateidentifier' => (string) $this->nodeAggregateIdentifier,
            'nodeidentifier' => (string) $this->nodeIdentifier,
            'dimensionspacepoint' => json_encode($this->dimensionSpacePoint),
            'dimensionspacepointhash' => (string) $this->dimensionSpacePointHash,
            'properties' => json_encode($this->properties),
            'nodetypename' => (string) $this->nodeTypeName,
            'hidden' => (int)$this->hidden
        ]);
    }

    public function updateToDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->update('neos_contentgraph_node', [
            'nodeaggregateidentifier' => (string) $this->nodeAggregateIdentifier,
            'nodeidentifier' => (string) $this->nodeIdentifier,
            'dimensionspacepoint' => json_encode($this->dimensionSpacePoint),
            'dimensionspacepointhash' => (string) $this->dimensionSpacePointHash,
            'properties' => json_encode($this->properties),
            'nodetypename' => (string) $this->nodeTypeName,
            'hidden' => (int)$this->hidden
        ],
        [
            'relationanchorpoint' => $this->relationAnchorPoint
        ]);
    }

    /**
     * @param Connection $databaseConnection
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
     */
    public static function fromDatabaseRow(array $databaseRow)
    {
        return new static(
            new NodeRelationAnchorPoint($databaseRow['relationanchorpoint']),
            new NodeIdentifier($databaseRow['nodeidentifier']),
            $databaseRow['nodeaggregateidentifier'] ? new NodeAggregateIdentifier($databaseRow['nodeaggregateidentifier']) : null,
            json_decode($databaseRow['dimensionspacepoint'], true),
            $databaseRow['dimensionspacepointhash'],
            json_decode($databaseRow['properties'], true),
            new NodeTypeName($databaseRow['nodetypename']),
            (bool)$databaseRow['hidden'],
            isset($databaseRow['name']) ? new NodeName($databaseRow['name']) : null
        );
    }
}
