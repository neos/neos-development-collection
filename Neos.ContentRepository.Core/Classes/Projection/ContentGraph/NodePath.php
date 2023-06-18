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
use Neos\ContentRepository\Core\SharedModel\Node\NodeName;

/**
 * The node path is a list of NodeNames. It can be either absolute or relative.
 *
 * It describes the hierarchy path of a node to a root node in a subgraph.
 * @api
 */
final class NodePath implements \JsonSerializable
{
    private function __construct(
        public readonly ?NodeTypeName $rootNodeTypeName,
        public readonly string $path
    ) {
        if ($this->path !== '/') {
            $pathParts = explode('/', ltrim($this->path, '/'));
            foreach ($pathParts as $pathPart) {
                if (preg_match(NodeName::PATTERN, $pathPart) !== 1) {
                    throw new \InvalidArgumentException(sprintf(
                        'The path "%s" is no valid NodePath because it contains a segment "%s"'
                        . ' that is no valid NodeName',
                        $this->path,
                        $pathPart
                    ), 1548157108);
                }
            }
        }
    }

    public static function fromString(string $path): self
    {
        if (\str_starts_with($path, '/<')) {
            $pivot = \mb_strpos($path, '>');
            $nodeTypeName = NodeTypeName::fromString(
                \mb_substr($path, 2, $pivot - 2)
            );
            $path = \mb_substr($path, $pivot + 2);
            if (empty($path)) {
                $path = '/';
            }
        } else {
            $nodeTypeName = null;
        }
        return new self($nodeTypeName, $path);
    }

    public static function tryFromString(string $string): ?self
    {
        try {
            return self::fromString($string);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }

    /**
     * @param array<int,string> $pathSegments
     */
    public static function fromPathSegments(array $pathSegments, ?NodeTypeName $rootNodeTypeName = null): self
    {
        if ($pathSegments === []) {
            return new self($rootNodeTypeName, '/');
        }
        return new self($rootNodeTypeName, '/' . implode('/', $pathSegments));
    }

    public function isRoot(): bool
    {
        return $this->path === '/';
    }

    public function isAbsolute(): bool
    {
        return $this->rootNodeTypeName instanceof NodeTypeName;
    }

    /**
     * IMMUTABLE function to create a new NodePath by appending a path segment. Returns a NEW NodePath object
     */
    public function appendPathSegment(NodeName $nodeName): self
    {
        return new self($this->rootNodeTypeName, $this->path . '/' . $nodeName->value);
    }

    /**
     * @return NodeName[]
     */
    public function getParts(): array
    {
        if ($this->isRoot()) {
            return [];
        }
        $pathParts = explode('/', ltrim($this->path, '/'));
        return array_map(static fn (string $pathPart) => NodeName::fromString($pathPart), $pathParts);
    }

    public function getDepth(): int
    {
        if (!$this->isAbsolute()) {
            throw new \RuntimeException(sprintf(
                'Depth of relative node path "%s" cannot be determined',
                $this->path
            ), 1548162166);
        }
        return count($this->getParts());
    }

    public function equals(NodePath $other): bool
    {
        return $this->path === $other->path
            && $this->rootNodeTypeName?->value === $other->rootNodeTypeName?->value;
    }

    public function __toString(): string
    {
        return $this->rootNodeTypeName
            ? rtrim('/<' . $this->rootNodeTypeName->value . '>/' . (ltrim($this->path, '/')), '/')
            : $this->path;
    }

    public function jsonSerialize(): string
    {
        return $this->__toString();
    }
}
