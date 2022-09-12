<?php

namespace Neos\ContentRepository\Core\Feature\NodeCreation\Dto;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * An assignment of NodeAggregateIds to NodePaths
 *
 * Usable for predefining NodeAggregateIds if multiple nodes are to be created simultaneously
 *
 * @api used as part of commands
 */
final class NodeAggregateIdsByNodePaths implements \JsonSerializable
{
    /**
     * Node aggregate ids, indexed by node path
     *
     * e.g. {main => my-main-node}
     *
     * @var array<string,NodeAggregateId>
     */
    protected array $nodeAggregateIds = [];

    /**
     * @param array<string,NodeAggregateId> $nodeAggregateIds
     */
    public function __construct(array $nodeAggregateIds)
    {
        foreach ($nodeAggregateIds as $nodePath => $nodeAggregateId) {
            $nodePath = NodePath::fromString($nodePath);
            if (!$nodeAggregateId instanceof NodeAggregateId) {
                throw new \InvalidArgumentException(
                    'NodeAggregateIdsByNodePaths objects can only be composed of NodeAggregateIds.',
                    1541751553
                );
            }

            $this->nodeAggregateIds[(string)$nodePath] = $nodeAggregateId;
        }
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    /**
     * @param array<string,string|NodeAggregateId> $array
     */
    public static function fromArray(array $array): self
    {
        $nodeAggregateIds = [];
        foreach ($array as $rawNodePath => $rawNodeAggregateId) {
            if (!is_string($rawNodePath)) {
                throw new \InvalidArgumentException(
                    'NodeAggregateIdsByNodePaths must be indexed by node path.',
                    1645632667
                );
            }
            if (is_string($rawNodeAggregateId)) {
                $nodeAggregateId = NodeAggregateId::fromString($rawNodeAggregateId);
            } elseif ($rawNodeAggregateId instanceof NodeAggregateId) {
                $nodeAggregateId = $rawNodeAggregateId;
            } else {
                throw new \InvalidArgumentException(
                    'NodeAggregateIdsByNodePaths must only contain NodeAggregateIds.',
                    1645632633
                );
            }
            $nodeAggregateIds[$rawNodePath] = $nodeAggregateId;
        }

        return new self($nodeAggregateIds);
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
        return new self(array_merge($this->nodeAggregateIds, $other->getNodeAggregateIds()));
    }

    public function getNodeAggregateId(NodePath $nodePath): ?NodeAggregateId
    {
        return $this->nodeAggregateIds[(string)$nodePath] ?? null;
    }

    public function add(NodePath $nodePath, NodeAggregateId $nodeAggregateId): self
    {
        $nodeAggregateIds = $this->nodeAggregateIds;
        $nodeAggregateIds[(string)$nodePath] = $nodeAggregateId;

        return new self($nodeAggregateIds);
    }

    /**
     * @return array<string,NodeAggregateId>
     */
    public function getNodeAggregateIds(): array
    {
        return $this->nodeAggregateIds;
    }

    /**
     * @return array<string,NodeAggregateId>
     */
    public function jsonSerialize(): array
    {
        return $this->nodeAggregateIds;
    }
}
