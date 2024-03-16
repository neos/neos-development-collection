<?php

declare(strict_types=1);

namespace Neos\Neos\Fusion\Cache;

use Neos\ContentRepository\Core\SharedModel\ContentRepository\ContentRepositoryId;
use Neos\ContentRepository\Core\NodeType\NodeTypeName;
use Neos\ContentRepository\Core\Projection\ContentGraph\Node;
use Neos\ContentRepository\Core\SharedModel\Node\NodeAggregateId;
use Neos\ContentRepository\Core\SharedModel\Workspace\ContentStreamId;
use Neos\Flow\Annotations as Flow;

/**
 * The cache tag value object
 */
#[Flow\Proxy(false)]
class CacheTag
{
    protected const PATTERN = '/^[a-zA-Z0-9_%\-&]{1,250}$/';

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

    final public static function forNodeAggregate(
        ContentRepositoryId $contentRepositoryId,
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
    ): self {
        return new self(
            'Node_'
            . self::getHashForContentStreamIdAndContentRepositoryId($contentStreamId, $contentRepositoryId)
            . '_' . $nodeAggregateId->value
        );
    }

    final public static function forNodeAggregateFromNode(Node $node): self
    {
        return self::forNodeAggregate(
            $node->subgraphIdentity->contentRepositoryId,
            $node->subgraphIdentity->contentStreamId,
            $node->nodeAggregateId
        );
    }

    final public static function forDescendantOfNode(
        ContentRepositoryId $contentRepositoryId,
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
    ): self {
        return new self(
            'DescendantOf_'
            . self::getHashForContentStreamIdAndContentRepositoryId($contentStreamId, $contentRepositoryId)
            . '_' . $nodeAggregateId->value
        );
    }

    final public static function forDescendantOfNodeFromNode(Node $node): self
    {
        return self::forDescendantOfNode(
            $node->subgraphIdentity->contentRepositoryId,
            $node->subgraphIdentity->contentStreamId,
            $node->nodeAggregateId
        );
    }

    final public static function forAncestorNode(
        ContentRepositoryId $contentRepositoryId,
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
    ): self {
        return new self(
            'Ancestor_'
            . self::getHashForContentStreamIdAndContentRepositoryId($contentStreamId, $contentRepositoryId)
            . '_' . $nodeAggregateId->value
        );
    }

    final public static function forAncestorNodeFromNode(Node $node): self
    {
        return self::forAncestorNode(
            $node->subgraphIdentity->contentRepositoryId,
            $node->subgraphIdentity->contentStreamId,
            $node->nodeAggregateId
        );
    }

    final public static function forNodeTypeName(
        ContentRepositoryId $contentRepositoryId,
        ContentStreamId $contentStreamId,
        NodeTypeName $nodeTypeName,
    ): self {
        return new self(
            'NodeType_'
            . self::getHashForContentStreamIdAndContentRepositoryId($contentStreamId, $contentRepositoryId)
            . '_' . \strtr($nodeTypeName->value, '.:', '_-')
        );
    }

    final public static function forDynamicNodeAggregate(
        ContentRepositoryId $contentRepositoryId,
        ContentStreamId $contentStreamId,
        NodeAggregateId $nodeAggregateId,
    ): self {
        return new self(
            'DynamicNodeTag_'
            . self::getHashForContentStreamIdAndContentRepositoryId($contentStreamId, $contentRepositoryId)
            . '_' . $nodeAggregateId->value
        );
    }

    final public static function fromString(string $string): self
    {
        return new self($string);
    }

    protected static function getHashForContentStreamIdAndContentRepositoryId(
        ContentStreamId $contentStreamId,
        ContentRepositoryId $contentRepositoryId,
    ): string {
        return sha1($contentStreamId->value . '@' . $contentRepositoryId->value);
    }
}
