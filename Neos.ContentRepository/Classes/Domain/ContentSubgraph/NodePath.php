<?php
declare(strict_types=1);

namespace Neos\ContentRepository\Domain\ContentSubgraph;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

use Neos\ContentRepository\Domain\Model\NodeInterface;
use Neos\ContentRepository\Domain\NodeAggregate\NodeName;
use Neos\Flow\Annotations as Flow;

/**
 * The node path is a list of NodeNames. It can be either absolute or relative.
 *
 * It describes the hierarchy path of a node to a root node in a subgraph.
 *
 * @Flow\Proxy(false)
 * @api
 */
final class NodePath implements \JsonSerializable
{
    /**
     * @var string
     */
    private $path;

    private function __construct(string $path)
    {
        if ($path !== '/') {
            $pathParts = explode('/', ltrim($path, '/'));
            foreach ($pathParts as $pathPart) {
                if (preg_match(NodeInterface::MATCH_PATTERN_NAME, $pathPart) !== 1) {
                    throw new \InvalidArgumentException(sprintf('The path "%s" is no valid NodePath because it contains a segment "%s" that is no valid NodeName', $path, $pathPart), 1548157108);
                }
            }
        }
        $this->path = $path;
    }

    public static function fromString(string $path): self
    {
        return new static($path);
    }

    public static function fromPathSegments(array $pathSegments): self
    {
        if ($pathSegments === []) {
            return new static('/');
        }
        return new static('/' . implode('/', $pathSegments));
    }

    public function isRoot(): bool
    {
        return $this->path === '/';
    }

    public function isAbsolute(): bool
    {
        return strpos($this->path, '/') === 0;
    }

    /**
     * IMMUTABLE function to create a new NodePath by appending a path segment. Returns a NEW NodePath object
     * @param NodeName $nodeName
     * @return NodePath
     */
    public function appendPathSegment(NodeName $nodeName): self
    {
        return new static($this->path . '/' . $nodeName);
    }

    /**
     * @return NodeName[]
     */
    public function getParts(): array
    {
        $pathParts = explode('/', ltrim($this->path, '/'));

        return array_map(function (string $pathPart) {
            return NodeName::fromString($pathPart);
        }, $pathParts);
    }

    public function getDepth(): int
    {
        if (!$this->isAbsolute()) {
            throw new \RuntimeException(sprintf('Depth of relative node path "%s" cannot be determined', $this->path), 1548162166);
        }
        return count($this->getParts());
    }

    public function equals(NodePath $other): bool
    {
        return (string) $this === (string) $other;
    }

    public function jsonSerialize()
    {
        return $this->path;
    }

    public function __toString()
    {
        return $this->path;
    }
}
