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
use Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate\NodeAggregateClassification;
use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\ContentRepository\Domain\NodeType\NodeTypeName;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValue;
use Neos\EventSourcedContentRepository\Domain\ValueObject\SerializedPropertyValues;
use Neos\Flow\Annotations as Flow;

/**
 * The active record for reading and writing nodes from and to the database
 */
#[Flow\Proxy(false)]
final class NodeRecord
{
    public function __construct(
        public NodeRelationAnchorPoint $relationAnchorPoint,
        public NodeAggregateIdentifier $nodeAggregateIdentifier,
        /** @var array<string,string> */
        public array $originDimensionSpacePoint,
        public string $originDimensionSpacePointHash,
        /** @var SerializedPropertyValues<string,SerializedPropertyValue> $properties */
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
    public function addToDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->insert('neos_contentgraph_node', [
            'relationanchorpoint' => (string)$this->relationAnchorPoint,
            'nodeaggregateidentifier' => (string)$this->nodeAggregateIdentifier,
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
    public function updateToDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->update(
            'neos_contentgraph_node',
            [
                'nodeaggregateidentifier' => (string)$this->nodeAggregateIdentifier,
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
    public function removeFromDatabase(Connection $databaseConnection): void
    {
        $databaseConnection->delete('neos_contentgraph_node', [
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
            NodeAggregateIdentifier::fromString($databaseRow['nodeaggregateidentifier']),
            json_decode($databaseRow['origindimensionspacepoint'], true),
            $databaseRow['origindimensionspacepointhash'],
            SerializedPropertyValues::fromArray(json_decode($databaseRow['properties'], true)),
            NodeTypeName::fromString($databaseRow['nodetypename']),
            NodeAggregateClassification::from($databaseRow['classification']),
            isset($databaseRow['name']) ? NodeName::fromString($databaseRow['name']) : null
        );
    }
}
