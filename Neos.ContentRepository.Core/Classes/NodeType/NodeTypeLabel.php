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

namespace Neos\ContentRepository\Core\NodeType;

/**
 * Human-readable label of a node type
 *
 * @api
 */
final class NodeTypeLabel implements \JsonSerializable
{
    private function __construct(
        public readonly string $value
    ) {
        if ($value === '') {
            throw new \InvalidArgumentException('Node type label must not be empty.', 1713621335);
        }
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function jsonSerialize(): string
    {
        return $this->value;
    }
}
