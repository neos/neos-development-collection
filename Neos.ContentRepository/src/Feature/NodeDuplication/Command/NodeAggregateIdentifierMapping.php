<?php

namespace Neos\ContentRepository\Feature\NodeDuplication\Command;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Feature\NodeDuplication\Command\NodeSubtreeSnapshot;
use Neos\ContentRepository\SharedModel\Node\NodeAggregateIdentifier;

/**
 * An assignment of "old" to "new" NodeAggregateIdentifiers
 *
 * Usable for predefining NodeAggregateIdentifiers if multiple nodes are copied.
 *
 * You'll never create this class yourself; but you use {@see CopyNodesRecursively::createFromSubgraphAndStartNode()}
 *
 * @internal implementation detail of {@see CopyNodesRecursively} command
 */
final class NodeAggregateIdentifierMapping implements \JsonSerializable
{
    /**
     * new Node aggregate identifiers, indexed by old node aggregate identifier
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
        foreach ($nodeAggregateIdentifiers as $oldNodeAggregateIdentifier => $newNodeAggregateIdentifier) {
            $oldNodeAggregateIdentifier = NodeAggregateIdentifier::fromString($oldNodeAggregateIdentifier);
            if (!$newNodeAggregateIdentifier instanceof NodeAggregateIdentifier) {
                throw new \InvalidArgumentException(
                    'NodeAggregateIdentifierMapping objects can only be composed of NodeAggregateIdentifiers.',
                    1573042379
                );
            }

            $this->nodeAggregateIdentifiers[(string)$oldNodeAggregateIdentifier] = $newNodeAggregateIdentifier;
        }
    }

    /**
     * Create a new identifier mapping, *GENERATING* new identifiers.
     */
    public static function generateForNodeSubtreeSnapshot(NodeSubtreeSnapshot $nodeSubtreeSnapshot): self
    {
        $nodeAggregateIdentifierMapping = [];
        $nodeSubtreeSnapshot->walk(
            function (NodeSubtreeSnapshot $nodeSubtreeSnapshot) use (&$nodeAggregateIdentifierMapping) {
                // here, we create new random NodeAggregateIdentifiers.
                $nodeAggregateIdentifierMapping[(string)$nodeSubtreeSnapshot->getNodeAggregateIdentifier()]
                    = NodeAggregateIdentifier::create();
            }
        );

        return new self($nodeAggregateIdentifierMapping);
    }

    /**
     * @param array<string,string> $array
     */
    public static function fromArray(array $array): self
    {
        $nodeAggregateIdentifiers = [];
        foreach ($array as $oldNodeAggregateIdentifier => $newNodeAggregateIdentifier) {
            $nodeAggregateIdentifiers[$oldNodeAggregateIdentifier]
                = NodeAggregateIdentifier::fromString($newNodeAggregateIdentifier);
        }

        return new self($nodeAggregateIdentifiers);
    }

    public function getNewNodeAggregateIdentifier(
        NodeAggregateIdentifier $oldNodeAggregateIdentifier
    ): ?NodeAggregateIdentifier {
        return $this->nodeAggregateIdentifiers[(string)$oldNodeAggregateIdentifier] ?? null;
    }

    /**
     * @return array<string,NodeAggregateIdentifier>
     */
    public function jsonSerialize(): array
    {
        return $this->nodeAggregateIdentifiers;
    }

    /**
     * @return array<int,NodeAggregateIdentifier>
     */
    public function getAllNewNodeAggregateIdentifiers(): array
    {
        return array_values($this->nodeAggregateIdentifiers);
    }
}
