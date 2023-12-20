<?php

/*
 * This file is part of the Neos.ContentGraph.DoctrineDbalAdapter package.
 *
 * (c) Contributors of the Neos Project - www.neos.io
 *
 * This package is Open Source Software. For the full copyright and license
 * information, please view the LICENSE file which was distributed with this
 * source code.
 */

declare(strict_types=1);

namespace Neos\ContentGraph\DoctrineDbalAdapter\Domain\Projection;

/**
 * The node relation anchor value object
 *
 * @internal
 */
class NodeRelationAnchorPoint implements \JsonSerializable
{
    public readonly int $value;

    private function __construct(int $value)
    {
        if ($value < 0) {
            throw new \InvalidArgumentException('A NodeRelationAnchorPoint cannot be negative, got %d', $value);
        }

        $this->value = $value;
    }

    public static function forRootEdge(): self
    {
        return new self(0);
    }

    public static function fromInteger(int $value): self
    {
        return new self($value);
    }

    public function jsonSerialize(): string
    {
        return (string)$this->value;
    }

    public function equals(self $other): bool
    {
        return $other->value === $this->value;
    }
}
