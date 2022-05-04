<?php

namespace Neos\ContentRepository\Feature\Common;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;
use Neos\ContentRepository\SharedModel\Node\NodePath;
use Neos\Flow\Annotations as Flow;

/**
 * An assignment of NodeAggregateIdentifiers to NodePaths
 *
 * Usable for predefining NodeAggregateIdentifiers if multiple nodes are to be created simultaneously
 */
#[Flow\Proxy(false)]
final class NodeAggregateIdentifiersByNodePaths implements \JsonSerializable
{
    /**
     * Node aggregate identifiers, indexed by node path
     *
     * e.g. {main => my-main-node}
     *
     * @var array<string,NodeAggregateIdentifier>
     */
    protected array $nodeAggregateIdentifiers = [];

    /**
     * @param array<string,NodeAggregateIdentifier> $nodeAggregateIdentifiers
     */
    public function __construct(array $nodeAggregateIdentifiers)
    {
        foreach ($nodeAggregateIdentifiers as $nodePath => $nodeAggregateIdentifier) {
            $nodePath = NodePath::fromString($nodePath);
            if (!$nodeAggregateIdentifier instanceof NodeAggregateIdentifier) {
                throw new \InvalidArgumentException(
                    'NodeAggregateIdentifiersByNodePaths objects can only be composed of NodeAggregateIdentifiers.',
                    1541751553
                );
            }

            $this->nodeAggregateIdentifiers[(string)$nodePath] = $nodeAggregateIdentifier;
        }
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    /**
     * @param array<string,string|NodeAggregateIdentifier> $array
     */
    public static function fromArray(array $array): self
    {
        $nodeAggregateIdentifiers = [];
        foreach ($array as $rawNodePath => $rawNodeAggregateIdentifier) {
            if (!is_string($rawNodePath)) {
                throw new \InvalidArgumentException(
                    'NodeAggregateIdentifiersByNodePaths must be indexed by node path.',
                    1645632667
                );
            }
            if (is_string($rawNodeAggregateIdentifier)) {
                $nodeAggregateIdentifier = NodeAggregateIdentifier::fromString($rawNodeAggregateIdentifier);
            } elseif ($rawNodeAggregateIdentifier instanceof NodeAggregateIdentifier) {
                $nodeAggregateIdentifier = $rawNodeAggregateIdentifier;
            } else {
                throw new \InvalidArgumentException(
                    'NodeAggregateIdentifiersByNodePaths must only contain NodeAggregateIdentifiers.',
                    1645632633
                );
            }
            $nodeAggregateIdentifiers[$rawNodePath] = $nodeAggregateIdentifier;
        }

        return new self($nodeAggregateIdentifiers);
    }

    /**
     * @throws \JsonException
     */
    public static function fromJsonString(string $jsonString): self
    {
        return self::fromArray(\json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR));
    }

    public function merge(self $other): self
    {
        return new self(array_merge($this->nodeAggregateIdentifiers, $other->getNodeAggregateIdentifiers()));
    }

    public function getNodeAggregateIdentifier(NodePath $nodePath): ?NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifiers[(string)$nodePath] ?? null;
    }

    public function add(NodePath $nodePath, NodeAggregateIdentifier $nodeAggregateIdentifier): self
    {
        $nodeAggregateIdentifiers = $this->nodeAggregateIdentifiers;
        $nodeAggregateIdentifiers[(string)$nodePath] = $nodeAggregateIdentifier;

        return new self($nodeAggregateIdentifiers);
    }

    /**
     * @return array<string,NodeAggregateIdentifier>
     */
    public function getNodeAggregateIdentifiers(): array
    {
        return $this->nodeAggregateIdentifiers;
    }

    /**
     * @return array<string,NodeAggregateIdentifier>
     */
    public function jsonSerialize(): array
    {
        return $this->nodeAggregateIdentifiers;
    }
}
