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

use Neos\ContentRepository\Domain\Utility\SubgraphUtility;
use Neos\Flow\Annotations as Flow;
use Neos\Utility\Arrays;

/**
 * Simple data model for writing nodes to the database
 */
class Node
{
    /**
     * @var string
     */
    public $identifierInGraph;

    /**
     * @var string
     */
    public $identifierInSubgraph;

    /**
     * @var string
     */
    public $subgraphIdentifier;

    /**
     * @var array
     */
    public $properties;

    /**
     * @var string
     */
    public $nodeTypeName;


    public function __construct(string $identifierInGraph, string $identifierInSubgraph, string $subgraphIdentifier, array $properties, string $nodeTypeName)
    {
        $this->identifierInGraph = $identifierInGraph;
        $this->identifierInSubgraph = $identifierInSubgraph;
        $this->subgraphIdentifier = $subgraphIdentifier;
        $this->properties = $properties;
        $this->nodeTypeName = $nodeTypeName;
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
            '_identifierInGraph' => $this->identifierInGraph,
            '_identifierInSubgraph' => $this->identifierInSubgraph,
            '_subgraphIdentifier' => $this->subgraphIdentifier,
            '_nodeTypeName' => $this->nodeTypeName
        ]);
    }
}
