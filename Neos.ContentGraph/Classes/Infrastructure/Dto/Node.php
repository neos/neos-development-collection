<?php

namespace Neos\ContentGraph\Infrastructure\Dto;

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
use Neos\ContentRepository\Domain\Context\Importing\Event\NodeWasImported;
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
    public $subgraphIdentifier;

    /**
     * @var string
     */
    public $subgraphIdentityHash;

    /**
     * @var array
     */
    public $properties;

    /**
     * @var string
     */
    public $nodeTypeName;


    /**
     * @param string $nodeIdentifier
     * @param string $nodeAggregateIdentifier
     * @param array $subgraphIdentifier
     * @param string $subgraphIdentityHash
     * @param array $properties
     * @param string $nodeTypeName
     */
    public function __construct(string $nodeIdentifier, string $nodeAggregateIdentifier, array $subgraphIdentifier, string $subgraphIdentityHash, array $properties, string $nodeTypeName)
    {
        $this->nodeIdentifier = $nodeIdentifier;
        $this->nodeAggregateIdentifier = $nodeAggregateIdentifier;
        $this->subgraphIdentifier = $subgraphIdentifier;
        $this->subgraphIdentityHash = $subgraphIdentityHash;
        $this->properties = $properties;
        $this->nodeTypeName = $nodeTypeName;
    }

    public static function fromRootNodeWasCreated(Event\RootNodeWasCreated $event): Node
    {
        return new Node(
            (string) $event->getNodeIdentifier(),
            '_system',
            [],
            'Neos.ContentGraph:Root'
        );
    }

    public static function fromNodeWasImported(NodeWasImported $event): Node
    {
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

    public function toArray(): array
    {
        return array_merge($this->properties, [
            '_nodeIdentifier' => $this->nodeIdentifier,
            '_nodeAggregateIdentifier' => $this->nodeAggregateIdentifier,
            '_subgraphIdentifier' => $this->subgraphIdentifier,
            '_subgraphIdentityHash' => $this->subgraphIdentityHash,
            '_nodeTypeName' => $this->nodeTypeName
        ]);
    }
}
