<?php

declare(strict_types=1);

namespace Neos\Neos\Domain\Service\NodeDuplication;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;

/**
 * An assignment of "old" to "new" NodeAggregateIds
 *
 * Usable for predefining NodeAggregateIds for deterministic testing, or fetching the newly inserted node.
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
    private array $nodeAggregateIds = [];

    /**
     * @param array<string,NodeAggregateId> $nodeAggregateIds
     */
    private function __construct(array $nodeAggregateIds)
    {
        foreach ($nodeAggregateIds as $oldNodeAggregateId => $newNodeAggregateId) {
            $oldNodeAggregateId = NodeAggregateId::fromString($oldNodeAggregateId);
            if (!$newNodeAggregateId instanceof NodeAggregateId) {
                throw new \InvalidArgumentException(
                    'NodeAggregateIdMapping objects can only be composed of NodeAggregateId.',
                    1573042379
                );
            }

            $this->nodeAggregateIds[$oldNodeAggregateId->value] = $newNodeAggregateId;
        }
    }

    public static function createEmpty(): self
    {
        return new self([]);
    }

    public function withNewNodeAggregateId(NodeAggregateId $oldNodeAggregateId, NodeAggregateId $newNodeAggregateId): self
    {
        $nodeAggregateIds = $this->nodeAggregateIds;
        $nodeAggregateIds[$oldNodeAggregateId->value] = $newNodeAggregateId;
        return new self($nodeAggregateIds);
    }

    /**
     * @param array<string,string|NodeAggregateId> $array
     */
    public static function fromArray(array $array): self
    {
        $nodeAggregateIds = [];
        foreach ($array as $oldNodeAggregateId => $newNodeAggregateId) {
            $nodeAggregateIds[$oldNodeAggregateId] = $newNodeAggregateId instanceof NodeAggregateId ? $newNodeAggregateId : NodeAggregateId::fromString($newNodeAggregateId);
        }

        return new self($nodeAggregateIds);
    }

    public function getNewNodeAggregateId(
        NodeAggregateId $oldNodeAggregateId
    ): ?NodeAggregateId {
        return $this->nodeAggregateIds[$oldNodeAggregateId->value] ?? null;
    }

    /**
     * @return array<string,NodeAggregateId>
     */
    public function jsonSerialize(): array
    {
        return $this->nodeAggregateIds;
    }
}
