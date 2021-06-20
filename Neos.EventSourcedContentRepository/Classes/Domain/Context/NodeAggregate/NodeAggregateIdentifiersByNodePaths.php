<?php

namespace Neos\EventSourcedContentRepository\Domain\Context\NodeAggregate;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\NodeAggregate\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ContentSubgraph\NodePath;

/**
 * An assignment of NodeAggregateIdentifiers to NodePaths
 *
 * Usable for predefining NodeAggregateIdentifiers if multiple nodes are to be created simultaneously
 */
final class NodeAggregateIdentifiersByNodePaths implements \JsonSerializable
{
    /**
     * Node aggregate identifiers, indexed by node path
     *
     * e.g. {main => my-main-node}
     *
     * @var array|NodeAggregateIdentifier[]
     */
    protected $nodeAggregateIdentifiers = [];

    public function __construct(array $nodeAggregateIdentifiers)
    {
        foreach ($nodeAggregateIdentifiers as $nodePath => $nodeAggregateIdentifier) {
            $nodePath = NodePath::fromString($nodePath);
            if (!$nodeAggregateIdentifier instanceof NodeAggregateIdentifier) {
                throw new \InvalidArgumentException('NodeAggregateIdentifiersByNodePaths objects can only be composed of NodeAggregateIdentifiers.', 1541751553);
            }

            $this->nodeAggregateIdentifiers[(string)$nodePath] = $nodeAggregateIdentifier;
        }
    }

    public static function createEmpty(): self
    {
        return new NodeAggregateIdentifiersByNodePaths([]);
    }

    public static function fromArray(array $array): self
    {
        $nodeAggregateIdentifiers = [];
        foreach ($array as $rawNodePath => $rawNodeAggregateIdentifier) {
            $nodeAggregateIdentifiers[$rawNodePath] = NodeAggregateIdentifier::fromString($rawNodeAggregateIdentifier);
        }

        return new NodeAggregateIdentifiersByNodePaths($nodeAggregateIdentifiers);
    }

    /**
     * @throws \JsonException
     */
    public static function fromJsonString(string $jsonString): self
    {
        return self::fromArray(\json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR));
    }

    public function merge(NodeAggregateIdentifiersByNodePaths $nodeAggregateIdentifiers): NodeAggregateIdentifiersByNodePaths
    {
        return new NodeAggregateIdentifiersByNodePaths(array_merge($this->nodeAggregateIdentifiers, $nodeAggregateIdentifiers->getNodeAggregateIdentifiers()));
    }

    public function getNodeAggregateIdentifier(NodePath $nodePath): ?NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifiers[(string)$nodePath] ?? null;
    }

    public function add(NodePath $nodePath, NodeAggregateIdentifier $nodeAggregateIdentifier): NodeAggregateIdentifiersByNodePaths
    {
        $nodeAggregateIdentifiers = $this->nodeAggregateIdentifiers;
        $nodeAggregateIdentifiers[(string)$nodePath] = $nodeAggregateIdentifier;

        return new NodeAggregateIdentifiersByNodePaths($nodeAggregateIdentifiers);
    }

    /**
     * @return array|NodeAggregateIdentifier[]
     */
    public function getNodeAggregateIdentifiers(): array
    {
        return $this->nodeAggregateIdentifiers;
    }

    public function jsonSerialize(): array
    {
        return $this->nodeAggregateIdentifiers;
    }
}
