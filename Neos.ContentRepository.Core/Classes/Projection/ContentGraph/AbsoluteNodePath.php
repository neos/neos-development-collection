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
 * It describes the hierarchy path of a node to and including its root node in a subgraph.
 * @api
 */
final class AbsoluteNodePath implements \JsonSerializable
{
    private function __construct(
        public readonly NodeTypeName $rootNodeTypeName,
        public readonly NodePath $path
    ) {
    }

    public static function fromComponents(
        NodeTypeName $rootNodeTypeName,
        NodePath $path
    ): self {
        return new self(
            $rootNodeTypeName,
            $path
        );
    }

    public static function fromString(string $value): self
    {
        if (\str_starts_with($value, '/<')) {
            $pivot = \mb_strpos($value, '>');
            if ($pivot > 0) {
                $nodeTypeName = NodeTypeName::fromString(\mb_substr($value, 2, $pivot - 2));
                $path = \mb_substr($value, $pivot + 2) ?: '/';
            } else {
                throw AbsoluteNodePathIsInvalid::becauseItDoesNotMatchTheRequiredPattern($value);
            }
        } else {
            throw AbsoluteNodePathIsInvalid::becauseItDoesNotMatchTheRequiredPattern($value);
        }
        return new self($nodeTypeName, NodePath::fromString($path));
    }

    public static function tryFromString(string $string): ?self
    {
        try {
            return self::fromString($string);
        } catch (AbsoluteNodePathIsInvalid) {
            return null;
        }
    }

    public function appendPathSegment(NodeName $nodeName): self
    {
        return new self(
            $this->rootNodeTypeName,
            $this->path->appendPathSegment($nodeName)
        );
    }

    public function isRoot(): bool
    {
        return $this->path->value === '/';
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
        return count($this->path->getParts());
    }

    public function equals(AbsoluteNodePath $other): bool
    {
        return $this->path === $other->path
            && $this->rootNodeTypeName->equals($other->rootNodeTypeName);
    }

    public function serializeToString(): string
    {
        return rtrim('/<' . $this->rootNodeTypeName->value . '>/' . (ltrim($this->path->value, '/')), '/');
    }

    public function jsonSerialize(): string
    {
        return $this->serializeToString();
    }
}
