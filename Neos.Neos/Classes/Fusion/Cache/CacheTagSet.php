<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion\Cache;

use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\NodeType\NodeTypeNames;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\Projection\ContentGraph\Nodes;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;

/**
 * The cache tag value object set
 */
#[Flow\Proxy(false)]
final class CacheTagSet
{
    /**
     * Unique cache tags, indexed by their value
     * @var array<string,CacheTag>
     */
    private array $tags;

    public function __construct(CacheTag ...$tags)
    {
        $uniqueTags = [];
        foreach ($tags as $tag) {
            $uniqueTags[$tag->value] = $tag;
        }

        $this->tags = $uniqueTags;
    }

    public static function forDescendantOfNodesFromNodes(
        Nodes $nodes
    ): self {
        return new self(...array_map(
            fn (Node $node): CacheTag => CacheTag::forDescendantOfNodeFromNode(
                $node
            ),
            iterator_to_array($nodes)
        ));
    }

    public static function forNodeAggregatesFromNodes(
        Nodes $nodes
    ): self {
        return new self(...array_map(
            fn (Node $node): CacheTag => CacheTag::forNodeAggregateFromNode(
                $node
            ),
            iterator_to_array($nodes)
        ));
    }


    public static function forNodeTypeNames(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName $workspaceName,
        NodeTypeNames $nodeTypeNames
    ): self {
        return new self(...array_map(
            fn (NodeTypeName $nodeTypeName): CacheTag => CacheTag::forNodeTypeName(
                $contentRepositoryId,
                $workspaceName,
                $nodeTypeName
            ),
            iterator_to_array($nodeTypeNames)
        ));
    }

    public function add(CacheTag $cacheTag): self
    {
        $tags = $this->tags;
        $tags[$cacheTag->value] = $cacheTag;

        return new self(...$tags);
    }

    /**
     * @return array<int,string>
     */
    public function toStringArray(): array
    {
        return array_map(
            fn (CacheTag $tag): string => $tag->value,
            array_values($this->tags)
        );
    }

    public function union(self $other): self
    {
        return new self(...array_merge($this->tags, $other->tags));
    }
}
