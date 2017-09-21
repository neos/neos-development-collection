<?php

namespace Neos\ContentGraph\Domain\Projection;

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
use Neos\Flow\Annotations as Flow;

/**
 * Simple data model for writing nodes to the database
 */
class Node
{
    /**
     * @var string
     */
    public $nodeIdentifier;

    /**
     * @var string
     */
    public $nodeAggregateIdentifier;

    /**
     * @var string
     */
    public $contentStreamIdentifier;

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
     * @var string
     */
    public $nodeTypeName;


    /**
     * Node constructor.
     * @param string $nodeIdentifier
     * @param string $nodeAggregateIdentifier
     * @param string $contentStreamIdentifier
     * @param array $dimensionSpacePoint
     * @param string $dimensionSpacePointHash
     * @param array $properties
     * @param string $nodeTypeName
     */
    public function __construct(string $nodeIdentifier, ?string $nodeAggregateIdentifier, ?string $contentStreamIdentifier, ?array $dimensionSpacePoint, ?string $dimensionSpacePointHash, array $properties, string $nodeTypeName)
    {
        $this->nodeIdentifier = $nodeIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->contentStreamIdentifier = $contentStreamIdentifier;
        $this->dimensionSpacePoint = $dimensionSpacePoint;
        $this->dimensionSpacePointHash = $dimensionSpacePointHash;
        $this->properties = $properties;
        $this->nodeTypeName = $nodeTypeName;
    }

    public static function fromRootNodeWasCreated(Event\RootNodeWasCreated $event): Node
    {
        return new Node(
            (string) $event->getNodeIdentifier(),
            null,
            (string) $event->getContentStreamIdentifier(),
            null,
            null,
            [],
            'Neos.ContentGraph:Root'
        );
    }


    public static function fromNodeAggregateWithNodeWasCreated(Event\NodeAggregateWithNodeWasCreated $event): Node
    {
        return new Node(
            (string) $event->getNodeIdentifier(),
            (string) $event->getNodeAggregateIdentifier(),
            (string) $event->getContentStreamIdentifier(),
            $event->getDimensionSpacePoint()->jsonSerialize(),
            $event->getDimensionSpacePoint()->getHash(),
            $event->getPropertyDefaultValuesAndTypes(),
            (string)$event->getNodeTypeName()
        );
    }



    /*
    public static function fromSystemNodeWasInserted(Event\SystemNodeWasInserted $event): Node
    {
        return new Node(
            $event->getVariantIdentifier(),
            $event->getIdentifier(),
            '_system',
            [],
            $event->getNodeType()
        );
    }

    public static function fromNodeWasInserted(Event\NodeWasInserted $event): Node
    {
        $subgraphIdentity = $event->getContentDimensionValues();
        $subgraphIdentity['contentStreamIdentifier'] = 'live';

        return new Node(
            $event->getVariantIdentifier(),
            $event->getIdentifier(),
            SubgraphUtility::hashIdentityComponents($subgraphIdentity),
            $event->getProperties(),
            $event->getNodeType()
        );
    }

    public static function fromNodeVariantWasCreated(Event\NodeVariantWasCreated $event, Node $fallbackNode): Node
    {
        $subgraphIdentity = $event->getContentDimensionValues();
        $subgraphIdentity['contentStreamIdentifier'] = 'live';

        $properties = $event->getStrategy() === Event\NodeVariantWasCreated::STRATEGY_COPY
            ? Arrays::arrayMergeRecursiveOverrule($fallbackNode->properties, $event->getProperties())
            : $event->getProperties();

        return new Node(
            $event->getVariantIdentifier(),
            $fallbackNode->identifierInSubgraph,
            SubgraphUtility::hashIdentityComponents($subgraphIdentity),
            $properties,
            $fallbackNode->nodeTypeName
        );
    } */
}
