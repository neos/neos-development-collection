<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion\Cache;

use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\WorkspaceName;
use Neos\Flow\Annotations as Flow;

/**
 * The cache tag value object
 */
#[Flow\Proxy(false)]
class CacheTag
{
    protected const PATTERN = '/^[a-zA-Z0-9_%\-&]{1,250}$/';
    protected const PREFIX_NODE = 'Node';
    protected const PREFIX_DESCENDANT_OF = 'DescendantOf';
    protected const PREFIX_ANCESTOR = 'Ancestor';
    protected const PREFIX_NODE_TYPE = 'NodeType';
    protected const PREFIX_DYNAMIC_NODE_TAG = 'DynamicNodeTag';
    protected const PREFIX_WORKSPACE = 'Workspace';

    private function __construct(
        public readonly string $value
    ) {
        if (preg_match(self::PATTERN, $value) !== 1) {
            throw new \InvalidArgumentException(
                'Given value "' . $value . '" is no valid cache tag, must match the defined pattern.',
                1658093413
            );
        }
    }

    private static function fromSegments(string ...$segments): self
    {
        return new self(implode('_', $segments));
    }

    final public static function forNodeAggregate(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName|CacheTagWorkspaceName $workspaceName,
        NodeAggregateId $nodeAggregateId,
    ): self {
        return self::fromSegments(
            self::PREFIX_NODE,
            self::getHashForWorkspaceNameAndContentRepositoryId($workspaceName, $contentRepositoryId),
            $nodeAggregateId->value,
        );
    }

    final public static function forNodeAggregateFromNode(Node $node): self
    {
        return self::forNodeAggregate(
            $node->contentRepositoryId,
            $node->workspaceName,
            $node->aggregateId
        );
    }

    final public static function forDescendantOfNode(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName|CacheTagWorkspaceName $workspaceName,
        NodeAggregateId $nodeAggregateId,
    ): self {
        return self::fromSegments(
            self::PREFIX_DESCENDANT_OF,
            self::getHashForWorkspaceNameAndContentRepositoryId($workspaceName, $contentRepositoryId),
            $nodeAggregateId->value,
        );
    }

    final public static function forDescendantOfNodeFromNode(Node $node): self
    {
        return self::forDescendantOfNode(
            $node->contentRepositoryId,
            $node->workspaceName,
            $node->aggregateId
        );
    }

    final public static function forAncestorNode(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName|CacheTagWorkspaceName $workspaceName,
        NodeAggregateId $nodeAggregateId,
    ): self {
        return self::fromSegments(
            self::PREFIX_ANCESTOR,
            self::getHashForWorkspaceNameAndContentRepositoryId($workspaceName, $contentRepositoryId),
            $nodeAggregateId->value,
        );
    }

    final public static function forAncestorNodeFromNode(Node $node): self
    {
        return self::forAncestorNode(
            $node->contentRepositoryId,
            $node->workspaceName,
            $node->aggregateId
        );
    }

    final public static function forNodeTypeName(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName|CacheTagWorkspaceName $workspaceName,
        NodeTypeName $nodeTypeName,
    ): self {
        return self::fromSegments(
            self::PREFIX_NODE_TYPE,
            self::getHashForWorkspaceNameAndContentRepositoryId($workspaceName, $contentRepositoryId),
            \strtr($nodeTypeName->value, '.:', '_-'),
        );
    }

    final public static function forDynamicNodeAggregate(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName|CacheTagWorkspaceName $workspaceName,
        NodeAggregateId $nodeAggregateId,
    ): self {
        return self::fromSegments(
            self::PREFIX_DYNAMIC_NODE_TAG,
            self::getHashForWorkspaceNameAndContentRepositoryId($workspaceName, $contentRepositoryId),
            $nodeAggregateId->value,
        );
    }

    final public static function forWorkspaceName(
        ContentRepositoryId $contentRepositoryId,
        WorkspaceName $workspaceName
    ) {
        return self::fromSegments(
            self::PREFIX_WORKSPACE,
            self::getHashForWorkspaceNameAndContentRepositoryId($workspaceName, $contentRepositoryId),
        );
    }

    final public static function fromString(string $string): self
    {
        return new self($string);
    }

    protected static function getHashForWorkspaceNameAndContentRepositoryId(
        WorkspaceName|CacheTagWorkspaceName $workspaceName,
        ContentRepositoryId $contentRepositoryId,
    ): string {
        return sha1(
            $workspaceName === CacheTagWorkspaceName::ANY ? $contentRepositoryId->value : $workspaceName->value . '@' . $contentRepositoryId->value
        );
    }
}
