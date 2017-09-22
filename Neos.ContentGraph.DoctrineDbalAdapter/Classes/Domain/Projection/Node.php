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
use Neos\ContentRepository\Domain\Context\Node\Event;
use Neos\ContentRepository\Domain\ValueObject\DimensionSpacePoint;
use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodeTypeName;
use Neos\Flow\Annotations as Flow;

/**
 * Simple data model for writing nodes to the database
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
     * Node constructor.
     * @param NodeRelationAnchorPoint $relationAnchorPoint
     * @param NodeIdentifier $nodeIdentifier
     * @param NodeAggregateIdentifier $nodeAggregateIdentifier
     * @param array $dimensionSpacePoint
     * @param string $dimensionSpacePointHash
     * @param array $properties
     * @param NodeTypeName $nodeTypeName
     */
    public function __construct(
        NodeRelationAnchorPoint $relationAnchorPoint,
        NodeIdentifier $nodeIdentifier,
        ?NodeAggregateIdentifier $nodeAggregateIdentifier,
        ?array $dimensionSpacePoint,
        ?string $dimensionSpacePointHash,
        ?array $properties,
        NodeTypeName $nodeTypeName
    ) {
        $this->relationAnchorPoint = $relationAnchorPoint;
        $this->nodeIdentifier = $nodeIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->dimensionSpacePointHash = $dimensionSpacePointHash;
        $this->properties = $properties;
        $this->nodeTypeName = $nodeTypeName;
    }


    public function addToDatabase($db) {
        // TODO
    }

    public static function fromDatabaseRow($databaseRow) {
        return new static(
            new NodeRelationAnchorPoint($databaseRow['relationanchorpoint']),
            new NodeIdentifier($databaseRow['nodeidentifier']),
            new NodeAggregateIdentifier($databaseRow['nodeaggregateidentifier']),
            json_decode($databaseRow['dimensionspacepoint'], true),
            $databaseRow['dimensionspacepointhash'],
            json_decode($databaseRow['properties'], true),
            new NodeTypeName($databaseRow['nodetypename'])
        );
    }

    // TODO MOVE OUT
    /**
     * @param NodeRelationAnchorPoint $relationAnchorPoint
     * @param Event\RootNodeWasCreated $event
     * @return Node
     */
    public static function fromRootNodeWasCreated(NodeRelationAnchorPoint $relationAnchorPoint, Event\RootNodeWasCreated $event): Node
    {
        return new Node(
            $relationAnchorPoint,
            $event->getNodeIdentifier(),
            null,
            null,
            null,
            [],
            new NodeTypeName('Neos.ContentRepository:Root')
        );
    }

    /**
     * @param NodeRelationAnchorPoint $relationAnchorPoint
     * @param Event\NodeAggregateWithNodeWasCreated $event
     * @return Node
     */
    public static function fromNodeAggregateWithNodeWasCreated(NodeRelationAnchorPoint $relationAnchorPoint, Event\NodeAggregateWithNodeWasCreated $event): Node
    {
        return new Node(
            $relationAnchorPoint,
            $event->getNodeIdentifier(),
            $event->getNodeAggregateIdentifier(),
            $event->getDimensionSpacePoint()->jsonSerialize(),
            $event->getDimensionSpacePoint()->getHash(),
            $event->getPropertyDefaultValuesAndTypes(),
            $event->getNodeTypeName()
        );
    }
}
