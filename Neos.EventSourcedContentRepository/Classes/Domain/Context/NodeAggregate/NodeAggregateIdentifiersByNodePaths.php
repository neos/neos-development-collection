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

use Neos\ContentRepository\Domain\ValueObject\NodeAggregateIdentifier;
use Neos\ContentRepository\Domain\ValueObject\NodePath;
use Neos\ContentRepository\Migration\Filters\NodeName;

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
    protected $nodeAggregateIdentifiers;

    public function __construct(array $nodeAggregateIdentifiers)
    {
        foreach ($nodeAggregateIdentifiers as $nodePath => $nodeAggregateIdentifier) {
            $nodePath = new NodePath($nodePath);
            if (!$nodeAggregateIdentifier instanceof NodeAggregateIdentifier) {
                throw new \InvalidArgumentException('NodeAggregateIdentifiersByNodePaths objects can only be composed of NodeAggregateIdentifiers.', 1541751553);
            }

            $this->nodeAggregateIdentifiers[(string) $nodePath] = $nodeAggregateIdentifier;
        }
    }

    public function merge(NodeAggregateIdentifiersByNodePaths $nodeAggregateIdentifiers): NodeAggregateIdentifiersByNodePaths
    {
        return new NodeAggregateIdentifiersByNodePaths(array_merge($this->nodeAggregateIdentifiers, $nodeAggregateIdentifiers->getNodeAggregateIdentifiers()));
    }

    public function getNodeAggregateIdentifier(NodePath $nodePath): ? NodeAggregateIdentifier
    {
        return $this->nodeAggregateIdentifiers[(string) $nodePath] ?? null;
    }

    public function add(NodePath $nodePath, NodeAggregateIdentifier $nodeAggregateIdentifier): NodeAggregateIdentifiersByNodePaths
    {
        $nodeAggregateIdentifiers = $this->nodeAggregateIdentifiers;
        $nodeAggregateIdentifiers[(string) $nodePath] = $nodeAggregateIdentifier;

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
