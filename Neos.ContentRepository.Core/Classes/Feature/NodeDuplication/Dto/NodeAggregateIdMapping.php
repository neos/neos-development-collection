<?php

namespace Neos\ContentRepository\Core\Feature\NodeDuplication\Dto;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\Feature\NodeDuplication\Dto\NodeSubtreeSnapshot;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * An assignment of "old" to "new" NodeAggregateIds
 *
 * Usable for predefining NodeAggregateIds if multiple nodes are copied.
 *
 * You'll never create this class yourself; but you use {@see CopyNodesRecursively::createFromSubgraphAndStartNode()}
 *
 * @internal implementation detail of {@see CopyNodesRecursively} command
 */
final class NodeAggregateIdMapping implements \JsonSerializable
{
    /**
     * new Node aggregate ids, indexed by old node aggregate id
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
        foreach ($nodeAggregateIds as $oldNodeAggregateId => $newNodeAggregateId) {
            $oldNodeAggregateId = NodeAggregateId::fromString($oldNodeAggregateId);
            if (!$newNodeAggregateId instanceof NodeAggregateId) {
                throw new \InvalidArgumentException(
                    'NodeAggregateIdMapping objects can only be composed of NodeAggregateId.',
                    1573042379
                );
            }

            $this->nodeAggregateIds[(string)$oldNodeAggregateId] = $newNodeAggregateId;
        }
    }

    /**
     * Create a new id mapping, *GENERATING* new ids.
     */
    public static function generateForNodeSubtreeSnapshot(NodeSubtreeSnapshot $nodeSubtreeSnapshot): self
    {
        $nodeAggregateIdMapping = [];
        $nodeSubtreeSnapshot->walk(
            function (NodeSubtreeSnapshot $nodeSubtreeSnapshot) use (&$nodeAggregateIdMapping) {
                // here, we create new random NodeAggregateIds.
                $nodeAggregateIdMapping[(string)$nodeSubtreeSnapshot->nodeAggregateId] = NodeAggregateId::create();
            }
        );

        return new self($nodeAggregateIdMapping);
    }

    /**
     * @param array<string,string> $array
     */
    public static function fromArray(array $array): self
    {
        $nodeAggregateIds = [];
        foreach ($array as $oldNodeAggregateId => $newNodeAggregateId) {
            $nodeAggregateIds[$oldNodeAggregateId] = NodeAggregateId::fromString($newNodeAggregateId);
        }

        return new self($nodeAggregateIds);
    }

    public function getNewNodeAggregateId(
        NodeAggregateId $oldNodeAggregateId
    ): ?NodeAggregateId {
        return $this->nodeAggregateIds[(string)$oldNodeAggregateId] ?? null;
    }

    /**
     * @return array<string,NodeAggregateId>
     */
    public function jsonSerialize(): array
    {
        return $this->nodeAggregateIds;
    }

    /**
     * @return array<int,NodeAggregateId>
     */
    public function getAllNewNodeAggregateIds(): array
    {
        return array_values($this->nodeAggregateIds);
    }
}
