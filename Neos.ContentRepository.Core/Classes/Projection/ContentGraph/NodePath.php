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
 * The relative node path is a collection of NodeNames. If it contains no elements, it is considered root.
 *
 * Example:
 * root path: '' is resolved to []
 * non-root path: 'my/site' is resolved to ~ ['my', 'site']
 *
 * It describes the hierarchy path of a node to an ancestor node in a subgraph.
 * @api
 */
final class NodePath implements \JsonSerializable
{
    /**
     * @deprecated use {@see self::serializeToString()} instead
     */
    public readonly string $value;

    /**
     * @var array<NodeName>
     */
    private readonly array $nodeNames;

    private function __construct(NodeName ...$nodeNames)
    {
        $this->nodeNames = $nodeNames;
        $this->value = $this->serializeToString();
    }

    public static function forRoot(): self
    {
        return new self();
    }

    public static function fromString(string $path): self
    {
        $path = ltrim($path, '/');
        if ($path === '') {
            return self::forRoot();
        }

        return self::fromPathSegments(
            explode('/', $path)
        );
    }

    /**
     * @param array<int,string> $pathSegments
     */
    public static function fromPathSegments(array $pathSegments): self
    {
        return new self(...array_map(
            function (string $pathPart) use ($pathSegments): NodeName {
                try {
                    return NodeName::fromString($pathPart);
                } catch (\InvalidArgumentException) {
                    throw new \InvalidArgumentException(sprintf(
                        'The path "%s" is no valid NodePath because it contains a segment "%s"'
                            . ' that is no valid NodeName',
                        implode('/', $pathSegments),
                        $pathPart
                    ), 1548157108);
                }
            },
            $pathSegments
        ));
    }

    public static function fromNodeNames(NodeName ...$nodeNames): self
    {
        return new self(...$nodeNames);
    }

    public function isRoot(): bool
    {
        return $this->getLength() === 0;
    }

    /**
     * IMMUTABLE function to create a new NodePath by appending a path segment. Returns a NEW NodePath object
     */
    public function appendPathSegment(NodeName $nodeName): self
    {
        return new self(
            ...$this->nodeNames,
            ...[$nodeName]
        );
    }

    /**
     * @return array<int,NodeName>
     */
    public function getParts(): array
    {
        return array_values($this->nodeNames);
    }

    public function getLength(): int
    {
        return count($this->nodeNames);
    }

    public function equals(NodePath $other): bool
    {
        return $this->serializeToString() === $other->serializeToString();
    }

    public function serializeToString(): string
    {
        return implode(
            '/',
            array_map(
                fn (NodeName $nodeName): string => $nodeName->value,
                $this->nodeNames
            )
        );
    }

    public function jsonSerialize(): string
    {
        return $this->serializeToString();
    }
}
