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
        public readonly string $value
    ) {
        if ($this->value !== '/') {
            $pathParts = explode('/', ltrim($this->value, '/'));
            foreach ($pathParts as $pathPart) {
                if (preg_match(NodeName::PATTERN, $pathPart) !== 1) {
                    throw new \InvalidArgumentException(sprintf(
                        'The path "%s" is no valid NodePath because it contains a segment "%s"'
                        . ' that is no valid NodeName',
                        $this->value,
                        $pathPart
                    ), 1548157108);
                }
            }
        }
    }

    public static function fromString(string $path): self
    {
        return new self($path);
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
    public static function fromPathSegments(array $pathSegments): self
    {
        if ($pathSegments === []) {
            return new self('/');
        }
        return new self('/' . implode('/', $pathSegments));
    }

    public function isRoot(): bool
    {
        return $this->value === '/';
    }

    /**
     * IMMUTABLE function to create a new NodePath by appending a path segment. Returns a NEW NodePath object
     */
    public function appendPathSegment(NodeName $nodeName): self
    {
        return new self($this->value . '/' . $nodeName->value);
    }

    /**
     * @return array<int,NodeName>
     */
    public function getParts(): array
    {
        if ($this->isRoot()) {
            return [];
        }
        $pathParts = explode('/', ltrim($this->value, '/'));
        return array_map(static fn (string $pathPart) => NodeName::fromString($pathPart), $pathParts);
    }

    public function getDepth(): int
    {
        throw new \RuntimeException(sprintf(
            'Depth of relative node path "%s" cannot be determined',
            $this->value
        ), 1548162166);
    }

    public function equals(NodePath $other): bool
    {
        return $this->value === $other->value;
    }

    public function serializeToString(): string
    {
        return $this->value;
    }

    public function jsonSerialize(): string
    {
        return $this->serializeToString();
    }
}
