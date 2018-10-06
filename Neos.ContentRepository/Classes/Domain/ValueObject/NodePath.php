<?php

namespace Neos\ContentRepository\Domain\ValueObject;

/*
 * This file is part of the Neos.ContentRepository package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

final class NodePath implements \JsonSerializable
{

    /**
     * @var string
     */
    private $path;

    public function __construct(string $path)
    {
        $this->path = $path;
    }

    public function isRoot(): bool
    {
        return $this->path === '/';
    }

    public function isAbsolute(): bool
    {
        return $this->path{0} === '/';
    }

    /**
     * IMMUTABLE function to create a new NodePath by appending a path segment. Returns a NEW NodePath object
     * @param NodeName $nodeName
     * @return NodePath
     */
    public function appendPathSegment(NodeName $nodeName): NodePath
    {
        return new NodePath($this->path . '/' . (string)$nodeName);
    }

    /**
     * @return NodeName[]
     */
    public function getParts(): array
    {
        $path = $this->path;
        if ($this->isAbsolute()) {
            $path = substr($path, 1);
        }
        $pathParts = explode('/', $path);

        return array_map(function ($pathPart) {
            return new NodeName($pathPart);
        }, $pathParts);
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
