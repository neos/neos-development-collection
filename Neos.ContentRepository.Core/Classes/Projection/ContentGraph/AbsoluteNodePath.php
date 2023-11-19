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

namespace Neos\ContentRepository\Core\Projection\ContentGraph;

use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\SharedModel\Exception\AbsoluteNodePathIsInvalid;
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;

/**
 * An absolute node path, composed of the root node's type and a relative path from there.
 *
 * Example:
 * root path: '/<Neos.ContentRepository:Root>' results in
 *      ~ {"rootNodeTypeName": "Neos.ContentRepository:Root", "path": []}
 * non-root path: '/<Neos.ContentRepository:Root>/my-site/main' results in
 *      ~ {"rootNodeTypeName": "Neos.ContentRepository:Root", "path": ["my-site", "main"]}
 *
 * It describes the hierarchy path of a node to and including its root node in a subgraph.
 *
 * To fetch a node via an absolute path use the subgraph: {@see ContentSubgraphInterface::findNodeByAbsolutePath()}
 *
 * ```php
 * $subgraph->findNodeByAbsolutePath(
 *     AbsoluteNodePath::fromString("/<Neos.Neos:Sites>/my-site/main")
 * )
 * ```
 *
 * @api
 */
final class AbsoluteNodePath implements \JsonSerializable
{
    private function __construct(
        public readonly NodeTypeName $rootNodeTypeName,
        public readonly NodePath $path
    ) {
    }

    public static function fromRootNodeTypeNameAndRelativePath(
        NodeTypeName $rootNodeTypeName,
        NodePath $path
    ): self {
        return new self(
            $rootNodeTypeName,
            $path
        );
    }

    /**
     * The ancestors must be ordered with the root node first.
     *
     * If you want to retrieve the path of a node using {@see ContentSubgraphInterface::findAncestorNodes()}, you need to reverse the order first {@see Nodes::reverse()}
     *
     * ```php
     * $ancestors = $this->findAncestorNodes($leafNode->nodeAggregateId, FindAncestorNodesFilter::create())->reverse();
     * $absoluteNodePath = AbsoluteNodePath::fromLeafNodeAndAncestors($leafNode, $ancestors);
     * ```
     */
    public static function fromLeafNodeAndAncestors(Node $leafNode, Nodes $ancestors): self
    {
        if ($leafNode->classification->isRoot()) {
            return new self($leafNode->nodeTypeName, NodePath::forRoot());
        }
        $rootNode = $ancestors->first();
        if (!$rootNode || !$rootNode->classification->isRoot()) {
            throw new \InvalidArgumentException(
                'Could not find a root node in ancestors',
                1687511170
            );
        }
        $ancestors = $ancestors->merge(Nodes::fromArray([$leafNode]));

        $nodeNames = [];
        foreach ($ancestors as $ancestor) {
            if ($ancestor->classification->isRoot()) {
                continue;
            }
            if (!$ancestor->nodeName) {
                throw new \InvalidArgumentException(
                    'Could not resolve node path for node ' . $leafNode->nodeAggregateId->value
                    . ', ancestor ' . $ancestor->nodeAggregateId->value . ' is unnamed.',
                    1687509348
                );
            }
            $nodeNames[] = $ancestor->nodeName;
        }

        return new self(
            $rootNode->nodeTypeName,
            NodePath::fromNodeNames(...$nodeNames)
        );
    }

    public static function patternIsMatchedByString(string $value): bool
    {
        if (!\str_starts_with($value, '/<')) {
            return false;
        }
        if (!(\mb_strpos($value, '>') > 0)) {
            return false;
        }

        return true;
    }

    public static function fromString(string $value): self
    {
        if (!self::patternIsMatchedByString($value)) {
            throw AbsoluteNodePathIsInvalid::becauseItDoesNotMatchTheRequiredPattern($value);
        }
        $pivot = \mb_strpos($value, '>') ?: 0; // pivot is actually > 0 due to the pattern check above
        $nodeTypeName = NodeTypeName::fromString(\mb_substr($value, 2, $pivot - 2));
        $path = \mb_substr($value, $pivot + 2) ?: '';

        return new self($nodeTypeName, NodePath::fromString($path));
    }

    public static function tryFromString(string $string): ?self
    {
        return self::patternIsMatchedByString($string)
            ? self::fromString($string)
            : null;
    }

    public function appendPathSegment(NodeName $nodeName): self
    {
        return new self(
            $this->rootNodeTypeName,
            $this->path->appendPathSegment($nodeName)
        );
    }

    /**
     * While all absolute node paths _have_ a root node type name,
     * they _are_ root only if they point to exactly that root node and not one of its descendants
     */
    public function isRoot(): bool
    {
        return $this->path->isRoot();
    }

    /**
     * @return array<int,NodeName>
     */
    public function getParts(): array
    {
        return $this->path->getParts();
    }

    public function getDepth(): int
    {
        return $this->path->getLength();
    }

    public function equals(AbsoluteNodePath $other): bool
    {
        return $this->path->equals($other->path)
            && $this->rootNodeTypeName->equals($other->rootNodeTypeName);
    }

    public function serializeToString(): string
    {
        return rtrim('/<' . $this->rootNodeTypeName->value . '>/' . $this->path->serializeToString(), '/');
    }

    public function jsonSerialize(): string
    {
        return $this->serializeToString();
    }
}
