<?php

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentRepository\Core\Feature\NodeCreation\Dto;

use Neos\ContentRepository\Core\Feature\NodeCreation\Command\CreateNodeAggregateWithNode;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\NodePath;
use Neos\ContentRepository\Core\SharedModel\Exception\NodeTypeNotFound;
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

            $this->nodeAggregateIds[$nodePath->serializeToString()] = $nodeAggregateId;
        }
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    /**
     * Generate the tethered node aggregate ids in advance
     *
     * {@see CreateNodeAggregateWithNode::withTetheredDescendantNodeAggregateIds}
     */
    public static function createForNodeType(NodeTypeName $nodeTypeName, NodeTypeManager $nodeTypeManager): self
    {
        return self::fromArray(self::createNodeAggregateIdsForNodeType($nodeTypeName, $nodeTypeManager));
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

    public static function fromJsonString(string $jsonString): self
    {
        try {
            return self::fromArray(\json_decode($jsonString, true, 512, JSON_THROW_ON_ERROR));
        } catch (\JsonException $e) {
            throw new \RuntimeException(sprintf('Failed to JSON-decode "%s": %s', $jsonString, $e->getMessage()), 1723032037, $e);
        }
    }

    public function merge(self $other): self
    {
        return new self(array_merge($this->nodeAggregateIds, $other->nodeAggregateIds));
    }

    public function completeForNodeOfType(NodeTypeName $nodeTypeName, NodeTypeManager $nodeTypeManager): self
    {
        return self::createForNodeType($nodeTypeName, $nodeTypeManager)
            ->merge($this);
    }

    /**
     * @return array<string,NodeAggregateId>
     */
    private static function createNodeAggregateIdsForNodeType(
        NodeTypeName $nodeTypeName,
        NodeTypeManager $nodeTypeManager,
        ?string $pathPrefix = null
    ): array {
        $nodeAggregateIds = [];
        $nodeType = $nodeTypeManager->getNodeType($nodeTypeName);
        if (!$nodeType) {
            throw new NodeTypeNotFound(sprintf('Cannot build NodeAggregateIdsByNodePaths because NodeType %s does not exist.', $nodeTypeName->value), 1715711379);
        }
        foreach ($nodeType->tetheredNodeTypeDefinitions as $tetheredNodeTypeDefinition) {
            $path = $pathPrefix ? $pathPrefix . '/' . $tetheredNodeTypeDefinition->name->value : $tetheredNodeTypeDefinition->name->value;
            $nodeAggregateIds[$path] = NodeAggregateId::create();
            $nodeAggregateIds = array_merge(
                $nodeAggregateIds,
                self::createNodeAggregateIdsForNodeType($tetheredNodeTypeDefinition->nodeTypeName, $nodeTypeManager, $path)
            );
        }

        return $nodeAggregateIds;
    }

    public function getNodeAggregateId(NodePath $nodePath): ?NodeAggregateId
    {
        return $this->nodeAggregateIds[$nodePath->serializeToString()] ?? null;
    }

    public function add(NodePath $nodePath, NodeAggregateId $nodeAggregateId): self
    {
        $nodeAggregateIds = $this->nodeAggregateIds;
        $nodeAggregateIds[$nodePath->serializeToString()] = $nodeAggregateId;

        return new self($nodeAggregateIds);
    }

    /**
     * @return array<string,NodeAggregateId>
     */
    public function getNodeAggregateIds(): array
    {
        return $this->nodeAggregateIds;
    }

    public function isEmpty(): bool
    {
        return $this->nodeAggregateIds === [];
    }

    /**
     * @return array<string,NodeAggregateId>
     */
    public function jsonSerialize(): array
    {
        return $this->nodeAggregateIds;
    }
}
