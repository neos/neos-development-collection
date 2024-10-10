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
        return new self(...$nodes->map(CacheTag::forDescendantOfNodeFromNode(...)));
    }

    public static function forDescendantOfNodesFromNodesWithoutWorkspace(
        Nodes $nodes,
    ): self {
        return new self(...$nodes->map(
            static fn (Node $node) => CacheTag::forDescendantOfNode(
                $node->contentRepositoryId,
                CacheTagWorkspaceName::ANY,
                $node->aggregateId,
            )
        ));
    }

    public static function forNodeAggregatesFromNodes(
        Nodes $nodes
    ): self {
        return new self(...$nodes->map(
            CacheTag::forNodeAggregateFromNode(...)
        ));
    }

    public static function forNodeAggregatesFromNodesWithoutWorkspace(
        Nodes $nodes
    ): self {
        return new self(...$nodes->map(
            static fn (Node $node) => CacheTag::forNodeAggregate(
                $node->contentRepositoryId,
                CacheTagWorkspaceName::ANY,
                $node->aggregateId
            )
        ));
    }

    public static function forNodeTypeNames(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName|CacheTagWorkspaceName $workspaceName,
        NodeTypeNames $nodeTypeNames
    ): self {
        return new self(...$nodeTypeNames->map(
            static fn (NodeTypeName $nodeTypeName): CacheTag => CacheTag::forNodeTypeName(
                $contentRepositoryId,
                $workspaceName,
                $nodeTypeName
            )
        ));
    }

    public static function forWorkspaceNameFromNodes(Nodes $nodes): self
    {
        return new self(...$nodes->map(
            static fn (Node $node): CacheTag => CacheTag::forWorkspaceName(
                $node->contentRepositoryId,
                $node->workspaceName,
            )
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
        return array_keys($this->tags);
    }

    public function union(self $other): self
    {
        return new self(...array_merge($this->tags, $other->tags));
    }
}
